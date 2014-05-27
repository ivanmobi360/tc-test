<?php
namespace tool;
use Utils;

class RequestTest extends \DatabaseBaseTest{
  
    public function testTrim(){
        $this->clearRequest();
        $_POST = ['foo' => 'bar'];
        $this->assertEquals('bar', Request::getPost('foo'));
        
        $this->clearRequest();
        $_POST = ['foo' => 'bar  '];
        $this->assertEquals('bar  ', Request::getPost('foo'));
        
        
        $this->clearRequest();
        $_POST = ['foo' => 'bar  '];
        Request::addFilter('trim');
        $this->assertEquals('bar', Request::getPost('foo'));
        
        
        
    }
    
    function testPost(){
        $this->clearRequest(); \Utils::clearLog();
        $_POST = ['foo' => '              bar'];
        Request::addFilter('trim');
        $post = Request::getPost();
        $this->assertEquals('bar', $post['foo']);
    }
  
    
}