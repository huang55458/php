<?php

namespace Home\Service;

class IndexService
{
    public function loginCheck($first_class = '', $second_class = ''): string
    {
        if (empty(session('user_id'))) {
//            $this->doResponse(ERRNO::NO_LOGIN, ERRNO::e(ERRNO::NO_LOGIN), []);
//            exit();
            return 'login';
        }
        if ($first_class === 'test') {
            return 'login';
        }
        return 'index';
    }
}