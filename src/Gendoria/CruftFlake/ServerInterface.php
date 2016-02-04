<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Gendoria\CruftFlake;

/**
 * @author Tomasz Struczyński <tomasz.struczynski@isobar.com>
 */
interface ServerInterface
{
    /**
     * Run CruftFlake server.
     * 
     * @return void
     */
    public function run();
}
