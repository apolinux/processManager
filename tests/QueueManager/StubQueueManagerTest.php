<?php

use PHPUnit\Framework\TestCase;
use ProcessManager\QueueManager\QueueManager;

class StubQueueManagerTest extends TestCase{
    
    public function testFirst(){
        $stub = $this->createMock(QueueManager::class);
        
        $stub->method('run')
                ->willReturn('bla');
        
        $this->assertEquals('bla',$stub->run());
    }
}
