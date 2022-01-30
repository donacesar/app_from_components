<?php


namespace App\Controllers;


use League\Plates\Engine;

class Error
{
    private $templates;

    public function __construct(Engine $templates) {
        $this->templates = $templates;
    }
    public function index(){
        echo $this->templates->render('error');
    }

}