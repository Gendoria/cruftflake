<?php

namespace Gendoria\CruftFlake\Command;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Gendoria\CruftFlake\Config\DoctrineConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Command to create doctrine configuration schema.
 *
 * @author Tomasz Struczyński <tomasz.struczynski@isobar.com>
 */
class DoctrineConfigSchemaCreateCommand extends Command
{

    /**
     * List of URL schemes from a database URL and their mappings to driver.
     */
    private static $driverSchemeAliases = array(
        'db2'        => 'ibm_db2',
        'mssql'      => 'pdo_sqlsrv',
        'mysql'      => 'pdo_mysql',
        'mysql2'     => 'pdo_mysql', // Amazon RDS, for some weird reason
        'postgres'   => 'pdo_pgsql',
        'postgresql' => 'pdo_pgsql',
        'pgsql'      => 'pdo_pgsql',
        'sqlite'     => 'pdo_sqlite',
        'sqlite3'    => 'pdo_sqlite',
    );
    
    protected function configure()
    {
        $this->setName('cruftflake:doctrine:schema-create')
            ->setDescription('Create Doctrine Config schema')
            ->addOption('dsn', null, InputOption::VALUE_REQUIRED, 'Connection DSN')
            ->addOption('table', null, InputOption::VALUE_REQUIRED, 'Doctrine table name', DoctrineConfig::DEFAULT_TABLE_NAME)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $connection = $this->getConnection($input, $output);
        DoctrineConfig::createTable($connection, $input->getOption('table'));
        $output->writeln('<info>Success</info>');
    }

    private function getConnection(InputInterface $input, OutputInterface $output)
    {
        /* @var $helper QuestionHelper */
        $helper = $this->getHelper('question');
        if ($input->getOption('dsn')) {
            return DriverManager::getConnection($this->parseDatabaseUrl(array(
                    'url' => $input->getOption('dsn'),
            )));
        }
        $qDsn = new Question("DSN: ");
        while (!$dsn = $helper->ask($input, $output, $qDsn)) {
            $output->writeln('<error>Please, enter a valid DSN</error>');
        }
        return DriverManager::getConnection($this->parseDatabaseUrl(array(
                'url' => $dsn
        )));
    }
    
    /**
     * Extracts parts from a database URL, if present, and returns an
     * updated list of parameters.
     *
     * @codeCoverageIgnore
     * @param array $params The list of parameters.
     *
     * @return array A modified list of parameters with info from a database
     *              URL extracted into indidivual parameter parts.
     *
     */
    private static function parseDatabaseUrl(array $params)
    {
        if (!isset($params['url'])) {
            return $params;
        }
        
        // (pdo_)?sqlite3?:///... => (pdo_)?sqlite3?://localhost/... or else the URL will be invalid
        $url = preg_replace('#^((?:pdo_)?sqlite3?):///#', '$1://localhost/', $params['url']);
        
        $url = parse_url($url);
        
        if ($url === false) {
            throw new DBALException('Malformed parameter "url".');
        }
        
        if (isset($url['scheme'])) {
            $params['driver'] = str_replace('-', '_', $url['scheme']); // URL schemes must not contain underscores, but dashes are ok
            if (isset(self::$driverSchemeAliases[$params['driver']])) {
                $params['driver'] = self::$driverSchemeAliases[$params['driver']]; // use alias like "postgres", else we just let checkParams decide later if the driver exists (for literal "pdo-pgsql" etc)
            }
        }
        
        if (isset($url['host'])) {
            $params['host'] = $url['host'];
        }
        if (isset($url['port'])) {
            $params['port'] = $url['port'];
        }
        if (isset($url['user'])) {
            $params['user'] = $url['user'];
        }
        if (isset($url['pass'])) {
            $params['password'] = $url['pass'];
        }
        
        if (isset($url['path'])) {
            if (!isset($url['scheme']) || (strpos($url['scheme'], 'sqlite') !== false && $url['path'] == ':memory:')) {
                $params['dbname'] = $url['path']; // if the URL was just "sqlite::memory:", which parses to scheme and path only
            } else {
                $params['dbname'] = substr($url['path'], 1); // strip the leading slash from the URL
            }
        }
        
        if (isset($url['query'])) {
            $query = array();
            parse_str($url['query'], $query); // simply ingest query as extra params, e.g. charset or sslmode
            $params = array_merge($params, $query); // parse_str wipes existing array elements
        }
        
        return $params;
    }    

}
