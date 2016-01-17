<?php

/**
 * The Keyword Model
 *
 * @author Hemant Mann
 */
class Keyword extends Shared\Model {

    /**
     * @column
     * @readwrite
     * @type integer
     * @index
     *
     * @validate required, numeric
     */
    protected $_user_id;

	/**
     * @column
     * @readwrite
     * @type text
     * @length 255
     * @index
     *
     * @validate required, max(255)
     */
    protected $_link;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 100
     * @index
     *
     * @validate required, min(3), max(100)
     */
    protected $_keyword;

    /**
     * @column
     * @readwrite
     * @type boolean
     *
     * @label If Serp if false then it is used for social media tracking
     */
    protected $_serp = true;
}
