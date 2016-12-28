<?php
class Entity {

    private $table = '';
    private $searchableFields = '*';

    public function __construct()
    {
    }

    public function setTable( $table ) {
        $this->table = $table;
    }

    /**
     * @return string
     */
    final protected function getTable() {
        return $this->table;
    }
    final protected function setSearchableFields($fields) {
        $this->searchableFields = $fields;
    }
    final protected function getSearchableFields() {
        return $this->searchableFields;
    }

    /**
     * Returns a record based on $idx
     *
     * @note
     *      - if it is invoked by REQUEST Query, then it ends with JSON
     *      - or it returns record.
     * @param $idx
     *      - table's idx or id field
     *      - it can be set through RESTFUL QUERY
     * @param string $fields
     *      - 'fields' to select which fields.
     *      - if invoked by RESTFUL QUERY, then in('fields') will be used.
     *
     * @param null $field
     *      - you can get any field using this.
     *      - $field cannot be used with Restful Query for security reason.
     * @return array|null|void
     * @Attention
     *      - when invoked by RESTFUL QUERY, in('id') can be used.
     * @note @importance @usage
     *
     *      - if you need to search, then use entity::search()
     *      - if you need to get more than one row, then use entity::search()
     *
     * @warning
     *      - if any one can get other's mobile number, that a serious security offense.
     *      - so, if security matters, then override this method on child object.
     *
     * @see user_entity_test::run()
     * @see user::get() for security.
     */
    public function get( $idx = null, $fields = '*', $field = null ) {
        $restful = false;
        if ( $idx === null ) { /// @attention if $idx is null, then it is Restful Query
            $idx = in('idx') ? in('idx') : in('id');
            if ( $idx ) {
                $restful = true;
                $fields = in('fields', '*');
            }
            else {
                print_r($_REQUEST);
                json_error(-40430, 'input-id-or-idx');
            }
        }
        if ( $field !== null ) {            /// @ATTENTION $field can be set only programmatically
            $id = db()->escape( $idx );
            $row = db()->get_row( "SELECT $fields FROM {$this->table} WHERE $field='$id'", ARRAY_A);
        }
        else if ( is_numeric( $idx ) ) $row = db()->get_row( "SELECT $fields FROM {$this->table} WHERE idx=$idx", ARRAY_A);
        else {
            $id = db()->escape( $idx );
            $row = db()->get_row( "SELECT $fields FROM {$this->table} WHERE id='$id'", ARRAY_A);
        }
        // db()->vardump();
        if ( $restful ) {
            if ( empty($row) ) json_error( -40431, "no-record");
            json_success( $row );
        }
        return $row;
    }

    /**
     * @param null $cond
     * @return null
     * @see user_entity_test::run()
     * @see post_test::count()
     */
    public function count( $cond = false ) {
        $restful = false;
        if ( $cond === false && in('mc') ) {
            $cond = in('cond');
            $restful = true;
        }
        if ( $cond ) $where = "WHERE $cond";
        else $where = null;
        $count = db()->get_var("SELECT COUNT(*) FROM {$this->table} $where");
        if ( $restful ) json_success( $count );
        return $count;
    }

    /**
     *
     * @param $options
     *      'cond' is the search condition. default is empty. no condition. search all.
     *      'page' is the number of the page. default is 1.
     *      'limit' is the number of rows. default is 10.
     * @warning for security matter, child class should set searchable fields.
     * @return array
     * @see user_entity_test::run()
     * @see post_test::search()
     * @update 2016-11-23 order by clause added.
     *
     */
    public function search( $options = false ) {
        $fields = $this->getSearchableFields();
        $table = $this->getTable();
        $restful = false;
        if ( $options === false && in('mc') ) {
            $restful = true;
            $options = in('options');
        }
        if ( isset($options['cond']) ) $cond = $options['cond'];
        else $cond = null;
        $count = $this->count( $cond );


        if ( $cond ) $where = "WHERE $cond";
        else $where = null;

        // get page number
        if ( isset($options['page'] ) ) $page = get_page_no( $options['page'] );
        else $page = 1;


        // order by.
        if ( isset($options['orderby']) ) $orderby = "ORDER BY $options[orderby]";
        else $orderby = '';


        // get limit to.
        if ( isset($options['limit'] ) ) $to = $options['limit'];
        else $to = 10;

        // get limit from
        $from = get_page_from( $page, $to );

        $q = "SELECT $fields FROM $table $where $orderby LIMIT $from, $to" ;
        $rows = db()->get_results( $q );


        $data = [];
        $data['total_count'] = $count; // count with condition but without limit
        $data['count'] = count($rows);
        $data['rows'] = $rows;
        $data['page'] = $page;
        $data['limit'] = $to;

        if ( $restful ) json_success( $data );
        return $data;
    }



    /**
     * Delete a record base on idx.
     * @Attention No RESTFUL QUERY due to security concern.
     * @param $idx - table's idx or id field.
     * @WARNING 'null' is not accepted. if 'null' is set, it will die()
     * @WARNING This is not RESTFUL INTERFACE !! Must overrides on child class if you want to use as restful.
     * @return bool|string
     *      - 1 is returned if $idx is null.
     *      - false is returned if success.
     */
    public function delete( $idx = null )
    {
        if ( $idx === null ) die("Entity::delete() called with null $idx");
        if ( is_numeric( $idx ) ) {
            $idx = db()->get_var("SELECT idx FROM {$this->table} WHERE idx='$idx'");
        }
        else {
            $id = db()->escape( $idx );
            $idx = db()->get_var( "SELECT idx FROM {$this->table} WHERE id='$id'");
        }
        if ( empty($idx) ) return 'record-not-exist';
        db()->query("DELETE FROM {$this->table} WHERE idx='$idx'");
        return false;
    }



}
