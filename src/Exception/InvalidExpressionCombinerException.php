<?php

/**
 * @author Bishwo Prasad Lamichhane <bishwo.prasad@gmail.com>
 */
namespace BplCrud\Exception;

class InvalidExpressionCombinerException extends \Exception{
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}