<?php
namespace App\Enums;
enum HttpStatusCode:int
{
    case SUCCESS = 200;
    case CREATED = 201;
    case UNAUTHORIZED = 401;
    case FORBIDDEN = 403;
    case NOTFOUND = 404;
    case INTERNAL_SERVER_ERROR = 500;
}
