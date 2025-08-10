<?php

namespace Http;

class Guest
{
    public function handle(): bool
    {
        echo "<p><i>O middleware <b>Guest</b> foi executado!</i></p>";

        $guest = true;
        if ($guest) {
            return true;
        }
        return false;
    }
}
