<?php

namespace App\Classes;

use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Query\Builder;
use Yajra\Datatables\Datatables;
//use Yajra\Datatables\Engines\BaseEngine;
use Yajra\DataTables\Services\DataTable;

class DataTableBase extends DataTable
{

    /*
    * The reason why this class exists is that Yajra/Datatables requires this service class for export methods to work
    * properly.
    */

    /** @var Builder The query that will be used to get the data from the db. */
    private $mQuery;

    /** @var array An array of columns */
    private $mColumns;


    private $mDataTable;

    /**
     * @param            $query

     * @param array $columns
     */
    public function __construct($query, $dataTable, $columns)
    {
        //parent::__construct(app(Datatables::class), app(Factory::class));

        $this->mQuery = $query;
        $this->mColumns = $columns;
        $this->mDataTable = $dataTable;
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajax()
    {
        return $this->mDataTable->make(true);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        return $this->mQuery;
    }

    public function html()
    {
        return $this->builder()->columns($this->mColumns);
    }

    protected function filename()
    {
        return 'job_' . date('YmdHis');
    }
}
