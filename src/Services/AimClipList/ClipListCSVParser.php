<?php

namespace Jk\Vts\Services\AimClipList;

use Jk\Vts\Services\Logging\LoggerTrait;
use ParseCsv\Csv;

class ClipListCSVParser {
    use LoggerTrait;
    private Csv $parser;
    private ClipListMeta $meta;
    public function __construct() {
        $this->parser = new Csv();
        $this->meta = new ClipListMeta();
    }
    public function parse(string $file) {
        $this->parser->parseFile($file);
        $this->log()->info("parsing csv");

        $this->log()->info("parsing validating csv");
        $error = $this->validate();
        if ($error instanceof \WP_Error) {
            return $error;
        }


        $this->log()->info("coercing csv data");
        return $this->coerceData();

    }

    private function coerceData() {
        $items = [];
        foreach ($this->parser->data as $row) {
            $item = [];
            $item = $this->meta->newItem($row);
            $items[] = $item;
        }
        $this->log()->info("retrieved items: ".count($items));
        return $items;
    }

    private function validate() {
        try {
            if (count($this->parser->data) < 1) {
                throw new \Exception("CSV is empty");
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return new \WP_Error('invalid_csv', $e->getMessage(), array('status' => 400));
        }
    }

}
