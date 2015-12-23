<?php

/**
 * The Referer Model
 *
 * @author Hemant Mann
 */
class Referer extends Shared\Model {

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
     * 
     * @validate required
     * @label url
     */
    protected $_url;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 255
     * 
     * @validate required
     * @label url
     */
    protected $_short_url;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 15
     * @index
     * 
     * @validate required
     * @label Referer
     */
    protected $_referer;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 8
     * 
     * @validate required
     * @label tld .com, .net
     */
    protected $_tld;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 255
     * 
     * @validate required
     * @label keyword
     */
    protected $_keyword;

}
