<?php
class MongoBase {
    private $manager = '';
    private $db = '';
    private $collection = '';

    /**
     * MongoBase constructor
     * @param $host
     * @param $port
     * @param $db
     * @param $collection
     */
    public function __construct($host, $port, $db, $collection) {
        $this->db = $db;
        $this->collection = $collection;
        $server = sprintf('mongodb://%s:%s', $host, $port);
        $this->manager = new MongoDB\Driver\Manager($server);
    }

    /**
     * insert
     * @param $data
     * @return \MongoDB\Driver\WriteResult
     */
    public function insert(&$data) {
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => true]);
        $oid = $bulk->insert($data);
        $data['_id'] = $oid;
        return $this->exec($this->collection, $bulk);
    }

    /**
     * delete
     * @param $query
     * @param bool $multi
     * @return \MongoDB\Driver\WriteResult
     */
    public function delete($query = [], $multi = false) {
        $limit = $multi ? 0 : 1;
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => true]);
        $bulk->delete($query, ['limit' => $limit]);
        return $this->exec($this->collection, $bulk);
    }

    /**
     * update
     * @param $query
     * @param $data
     * @param bool $upsert
     * @param bool $multi
     * @return \MongoDB\Driver\WriteResult
     */
    public function update($query, $data, $upsert = false, $multi = false) {
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => true]);
        $bulk->update($query ,['$set' => $data], ['multi' => $multi, 'upsert' => $upsert]);
        return $this->exec($this->collection, $bulk);
    }

    /**
     * findOne
     * @param $query
     * @return array
     */
    public function findOne($query = []) {
        $options = ['limit' => 1];
        $Query = new MongoDB\Driver\Query($query, $options);
        $cursor = $this->manager->executeQuery($this->db.'.'.$this->collection, $Query);
        $ret = [];
        foreach($cursor as $document) {
            $ret = (array)$document;
        }
        return $ret;
    }

    /**
     * find
     * @param $query
     * @param array $fields
     * @param $sort
     * @param int $skip
     * @param int $limit
     * @return array
     */
    public function find($query = [], $fields = [], $sort = [], $skip = 0, $limit = 20) {
        $options = array(
            'projection' => $fields,
            'sort' => $sort,
            'skip' => $skip,
            'limit' => $limit
        );
        $Query = new MongoDB\Driver\Query($query, $options);
        $cursor = $this->manager->executeQuery($this->db.'.'.$this->collection, $Query);
        $ret = [];
        foreach($cursor as $document) {
            $ret[] = (array)$document;
        }
        return $ret;
    }

    /**
     * count
     * @param $query
     * @return int
     */
    public function count($query = []) {
        $cmd = new MongoDB\Driver\Command(['count' => $this->collection, 'query' => $query]);
        $cursor = $this->manager->executeCommand($this->db, $cmd);
        $result = current($cursor->toArray());
        if ( ! isset($result->n) || ! (is_integer($result->n) || is_float($result->n))) {
            throw new UnexpectedValueException('count command did not return a numeric "n" value');
        }
        return (integer) $result->n;
    }

    /**
     * @param $collection
     * @param $bulk
     * @return \MongoDB\Driver\WriteResult
     */
    private function exec($collection, $bulk) {
        $writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 1000);
        $result = $this->manager->executeBulkWrite($this->db.'.'.$collection, $bulk, $writeConcern);
        return $result;
    }
}

