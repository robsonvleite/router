<?php

namespace Http;

class Group
{
    public function handle(): bool
    {
        echo "<p><i>O middleware <b>Group</b> foi executado!</i></p>";

        $group = true;
        if ($group) {
            return true;
        }
        return false;
    }
}