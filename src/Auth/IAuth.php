<?php

namespace JVVM\Auth;

use JVVM\Utils\ID;

interface IAuth {
    function login (
        string $identifier,
        string $parameter1 = '',
        string $parameter2 = '',
        string $parameter3 = '',
        string $parameter4 = ''
    ):ID|false;
 
    function update (
        ID $member_id,
        string $identifier,
        string $parameter1 = '',
        string $parameter2 = '',
        string $parameter3 = '',
        string $parameter4 = ''
    ):ID|false;
    
}