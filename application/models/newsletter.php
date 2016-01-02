<?php

/**
 * Description of newsletter
 *
 * @author Faizan Ayubi
 */
class Newsletter extends Shared\Model {
    
    /**
     * @column
     * @readwrite
     * @type integer
     * @index
     */
    protected $_template_id;
    
    /**
     * @column
     * @readwrite
     * @type integer
     * @index
     */
    protected $_group_id;
    
    /**
     * @column
     * @readwrite
     * @type date
     */
    protected $_scheduled;
}