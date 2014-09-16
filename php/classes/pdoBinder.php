<?php

Class PDOConfig Extends PDO 
{
    private $engine;
    private $host;
    private $database;
    private $user;
    private $pass;
   
    public function __construct()
    {
        $this->engine   = 'mysql';
        $this->host     = 'localhost';
        $this->database = 'filson';
        $this->user     = 'root';
        $this->pass     = '';
        $dns = $this->engine.':dbname='.$this->database.";host=".$this->host;
        parent::__construct( $dns, $this->user, $this->pass );
    }
}


Class Connection
{
    public  $slice      = 0;

    private $total      = 0,
            $firstId    = 0,
            $lastId     = 0;

    private $map        = [];

    public function __construct()
    {
        $this->dbh = New \PDOConfig();
        $this->dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
    }

    public function display($type = false)
    {
        if (isset($this->$type)) print_r($this->$type);

            return $this;
    }

    public function setTotals()
    {
        $res = $this->dbh->query('SELECT count(id) AS total FROM wds_user');
        $res->execute();

        $obj = $res->fetchAll(PDO::FETCH_CLASS, 'ArrayObject') [0];

            $this->total = $obj->total;

        $res = $this->dbh->query('SELECT id AS firstId FROM wds_user ORDER BY id ASC LIMIT 1');
        $res->execute();

        $obj = $res->fetchAll(PDO::FETCH_CLASS, 'ArrayObject') [0];

            $this->firstId = $obj->firstId;

        $res = $this->dbh->query('SELECT id AS lastId FROM wds_user ORDER BY id DESC LIMIT 1');
        $res->execute();

        $obj = $res->fetchAll(PDO::FETCH_CLASS, 'ArrayObject') [0];

            $this->lastId = $obj->lastId;

        return $this;
    }

    public function chunk($range = 100)
    {
        $i = $this->firstId; //2

        while (true)
        {
            $c = ($i + $range);
            if ($this->lastId < $c) 
            {
                $this->map[] = [
                    (int) $i, 
                    (int) $this->lastId]
                ;

                return $this;
            }

            $this->map[] = [
                (int) $i, 
                (int) $c
            ]; 

            $i = $c++;
        }
    }

    public function getCustomers()
    {
        if (isset($this->map[$this->slice]))
        {
            echo "\nSlice: {$this->map[$this->slice][0]}, {$this->map[$this->slice][1]}\n\n";

            $res = $this->dbh->prepare(trim(
                'SELECT 
                    ins_by          AS userid,
                    wds_user_id     AS customer, 
                    wds_address_id  AS billing,
                    (
                        SELECT wds_address_id  AS shipping
                        FROM wds_user_address B
                        WHERE ins_by != "GUEST" AND wds_address_type_id = 2 AND B.ins_by = A.ins_by
                    ) AS shipping

                FROM 

                    wds_user_address A 

                WHERE ins_by != "GUEST" AND wds_address_type_id = 1

                 ORDER BY id ASC LIMIT :low, :high'
             ));


            $res->bindParam(':low',  $this->map[$this->slice][0], PDO::PARAM_INT);
            $res->bindParam(':high', $this->map[$this->slice][1], PDO::PARAM_INT);
            $res->execute();

            $this->map[$this->slice] = $res->fetchAll(PDO::FETCH_OBJ);

            $this->getCustomerLists();
        }
        return $this;
    }


    protected function getCustomerLists()
    {
        if (isset($this->map[$this->slice]) && ! empty($this->map[$this->slice]))
        {
            foreach ($this->map[$this->slice] AS $id => &$object)
            {

                // General Customer Info
                $res = $this->dbh->prepare(trim(
                    'SELECT 
                        ""              AS prefix,
                        ""              AS suffix,
                        id,
                        active_bl   AS active,
                        company_id  AS corpId,
                        email,
                        password,
                        first_name  AS firstname,
                        middle_name AS middlename,
                        last_name   AS lastname

                     FROM 

                        wds_user

                     WHERE

                        id = :id'
                ));

                $res->bindParam(':id',  $object->customer, PDO::PARAM_INT);
                $res->execute();

                $object->customer = $res->fetchAll(PDO::FETCH_OBJ) [0];


                // Billing Address
                $res = $this->dbh->prepare(
                    'SELECT 
                        wda.id,
                        wda.company,
                        wda.street1,
                        wda.street2,
                        wda.street3,
                        wda.city,
                        wda.state,
                        wda.postal_code AS postal,
                        zcc.code AS countryId,
                        zcc.name AS countryName
                     FROM 
                        wds_address wda,
                        zc_country zcc
                     WHERE 
                        wda.country_id = zcc.id
                     AND
                        wda.id = :id'
                );

                $res->bindParam(':id',  $object->billing, PDO::PARAM_INT);
                $res->execute();

                $object->billing = $res->fetchAll(PDO::FETCH_OBJ) [0];

                // Shipping Address
                $res = $this->dbh->prepare(
                    'SELECT 
                        wda.id,
                        wda.company,
                        wda.street1,
                        wda.street2,
                        wda.street3,
                        wda.city,
                        wda.state,
                        wda.postal_code AS postal,
                        zcc.code AS countryId,
                        zcc.name AS countryName
                     FROM 
                        wds_address wda,
                        zc_country zcc
                     WHERE 
                        wda.country_id = zcc.id
                     AND
                        wda.id = :id'
                );

                $res->bindParam(':id',  $object->shipping, PDO::PARAM_INT);
                $res->execute();

                $object->shipping = $res->fetchAll(PDO::FETCH_OBJ) [0];

                // Phone Number
                $res = $this->dbh->prepare(
                    'SELECT 
                        wdp.phone_number AS phone
                     FROM 
                        wds_phone wdp, 
                        wds_address_phone wda
                     WHERE 
                        wda.wds_phone_id = wdp.id
                     AND 
                        wda.wds_address_id = :id'
                );

                $res->bindParam(':id',  $object->shipping->id, PDO::PARAM_INT);
                $res->execute();
                
                $result = $res->fetchAll(PDO::FETCH_OBJ);

                $object->customer->phone = (isset($result [0]) && ! empty($result [0]))
                    ? $result [0]->phone
                    : null;
            }
        }
        return $this;
    }

    public function getSlice()
    {
        return $this->map[$this->slice];
    }

    public function set($type)
    {
        $this->$type = $this->map[$this->slice];
        $this->slice++;

        return (isset($this->map[$this->slice]) && ! empty($this->map[$this->slice])) 
            ? $this
            : false;
    }
}
