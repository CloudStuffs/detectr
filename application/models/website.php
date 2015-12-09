<?php

/**
 * The Website Model
 *
 * @author Faizan Ayubi
 */
class Website extends Shared\Model {

    /**
     * @column
     * @readwrite
     * @type integer
     * @index
     */
    protected $_user_id;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 255
     * 
     * @validate required, alpha, min(3), max(32)
     * @label title
     */
    protected $_title;

    /**
     * @column
     * @readwrite
     * @type text
     * @index
     * 
     * @validate required
     * @label url
     */
    protected $_url;

}
