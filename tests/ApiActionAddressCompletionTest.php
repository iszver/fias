<?php

use ApiAction\AddressCompletion;

class ApiActionAddressCompletionTest extends TestAbstract
{
    public function testNotFound()
    {
        $complete = new AddressCompletion($this->db, 'Нави, Главная б', 50);
        $this->assertCount(0, $complete->run());
    }

    public function testAddressCompletion()
    {
        $complete = new AddressCompletion($this->db, 'г Москва, Ста', 50);
        $result   = $complete->run();

        $this->assertCount(4, $result);
        $this->assertEquals('г Москва, пр Ставропольский', $result[0]['title']);
        $this->assertEquals(0, $result[0]['is_complete']);
    }

    public function testHomeCompletion()
    {
        $complete = new AddressCompletion($this->db, 'г Москва, ул Стахановская, 1', 2);
        $result   = $complete->run();

        $this->assertCount(2, $result);
        $this->assertEquals('г Москва, ул Стахановская, 1к1', $result[0]['title']);
        $this->assertEquals(1, $result[0]['is_complete']);
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Некорректное значение
     */
    public function testBadMaxDepth()
    {
        new AddressCompletion($this->db, 'Моск', 50, 'totally_wrong_max_depth');
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Некорректное значение
     */
    public function testBadAddressLevels()
    {
        new AddressCompletion($this->db, 'Моск', 50, 'region', array('region', 'putin\'s village', 'street'));
    }

    public function testMaxDepth()
    {
        $complete = new AddressCompletion($this->db, 'г Москва, Ста', 50, 'region');
        $this->assertEmpty($complete->run());

        $complete = new AddressCompletion($this->db, 'Моск', 50, 'region');
        $this->assertCount(1, $complete->run());
    }

    public function testAddressLevels()
    {
        $complete = new AddressCompletion($this->db, 'Моск', 50, null, array('street'));
        $this->assertEmpty($complete->run());

        $complete = new AddressCompletion($this->db, 'Моск', 50, null, array('region'));
        $this->assertCount(1, $complete->run());

        $complete = new AddressCompletion($this->db, 'г Москва, Ста', 50, null, array('region', 'street'));
        $this->assertCount(4, $complete->run());
    }

    public function testRegion()
    {
        $complete = new AddressCompletion($this->db, 'Моск', 50, null, array(), array(78));
        $this->assertEmpty($complete->run());

        $complete = new AddressCompletion($this->db, 'Моск', 50, null, array(), array(77));
        $this->assertCount(1, $complete->run());
    }
}
