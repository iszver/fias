<?php
use Grace\DBAL\ConnectionAbstract\ConnectionInterface;

class AddressStorage
{
    /** @var ConnectionInterface */
    private $db;

    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    public function findAddress($address)
    {
        $level = count(explode(',', $address)) - 1;
        $sql   = '
            SELECT *
            FROM address_objects
            WHERE level = ?q
                AND lower(full_title) = lower(?q)
            LIMIT 1'
        ;

        return $this->db->execute($sql, array($level, $address))->fetchOneOrFalse();
    }

    public function findHouse($address)
    {
        $tmp   = explode(',', $address);
        $house = trim(array_pop($tmp));

        $address = $this->findAddress(implode(',', $tmp));
        if ($address) {
            $addressId = $address['address_id'];
            $sql       = '
                SELECT *
                FROM houses
                WHERE address_id = ?q
                    AND full_number = lower(?q)
                LIMIT 1'
            ;

            return $this->db->execute($sql, array($addressId, $house))->fetchOneOrFalse();
        }

        return null;
    }

    public function findAddressByPostalCode($postalCode)
    {
        // STOPPPER рекурсивный запрос на родителей объекта, полученных по индексу.
        // STOPPER разбор на составляющие по уровню?
        $sql = '
            SELECT title
            FROM address_objects
            WHERE postal_code = ?q
            ORDER BY level DESC'
        ;

        return $this->db->execute($sql, array($postalCode))->fetchAll();
    }

    public function findAddressById($id)
    {
        $sql = '
            SELECT *
            FROM address_objects
            WHERE address_id = ?q
            ORDER BY level DESC
            LIMIT 1'
        ;

        return $this->db->execute($sql, array($id))->fetchOneOrFalse();
    }

    public function findHousesByPostalCode($postalCode)
    {
        $sql = '
            SELECT *
            FROM houses
            WHERE postal_code = ?q'
        ;

        return $this->db->execute($sql, array($postalCode))->fetchResult();
    }
}
