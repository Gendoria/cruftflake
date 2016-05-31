<?php

namespace Gendoria\CruftFlake\Command;

use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Description of DoctrineConfigSchemaCreateCommandTest
 *
 * @author Tomasz Struczyński <tomasz.struczynski@isobar.com>
 */
class DoctrineConfigSchemaCreateCommandTest extends PHPUnit_Framework_TestCase
{
    public function testExecuteWithDsnOption()
    {
        $application = new Application();
        $application->add(new DoctrineConfigSchemaCreateCommand());
        $command = $application->find('cruftflake:doctrine:schema-create');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command' => $command->getName(),
            '--dsn' => 'sqlite://memory'
        ));

        $this->assertRegExp('/Success/', $commandTester->getDisplay());
    }

    public function testExecuteWithoutDsnOption()
    {
        $application = new Application();
        $application->add(new DoctrineConfigSchemaCreateCommand());
        $command = $application->find('cruftflake:doctrine:schema-create');
        $question = $this->getMock('\Symfony\Component\Console\Helper\QuestionHelper');
        $question->expects($this->once())
            ->method('ask')
            ->will($this->returnValue('sqlite://memory'));
        $command->getHelperSet()->set($question, 'question');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command' => $command->getName(),
        ));

        $this->assertRegExp('/Success/', $commandTester->getDisplay());
    }
    
    public function testExecuteWithoutDsnOptionOneError()
    {
        $application = new Application();
        $application->add(new DoctrineConfigSchemaCreateCommand());
        $command = $application->find('cruftflake:doctrine:schema-create');
        $question = $this->getMock('\Symfony\Component\Console\Helper\QuestionHelper');
        $question->expects($this->exactly(2))
            ->method('ask')
            ->willReturnOnConsecutiveCalls(null, 'sqlite://memory');
        $command->getHelperSet()->set($question, 'question');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command' => $command->getName(),
        ));

        $this->assertRegExp('/Please, enter a valid DSN/', $commandTester->getDisplay());
        $this->assertRegExp('/Success/', $commandTester->getDisplay());
    }
    
}
