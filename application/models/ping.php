<?php

/**
 * The Ping model
 *
 * @author Shreyansh Goel
 */
class Ping extends Shared\Model {

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
     * @validate required, alpha, max(32)
     * @label title
     */
    protected $_title;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 255
     * @index
     * 
     * @validate required
     * @label It is the domain only (like playmusic.net)
     */
    protected $_url;

    /**
     * @column
     * @readwrite
     * @type boolean
     * 
     * @label 
     */
    protected $_live = true;


    /**
     * @column
     * @readwrite
     * @type boolean
     * 
     * @label time interval minutes
     */
    protected $_interval = NULL;

}
