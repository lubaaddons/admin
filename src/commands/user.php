<?php

namespace Luba\Commands;

use SQL;

class user extends Command
{

    const AVAILABLE_COMMANDS = "Available parameters for user:\nadd|setpassword|refresh|rebuild";

    /**
     * Adds a new user
     *
     * @return void
     */
    public function adduser() {
        print "Please enter username:";
        $handle = fopen ("php://stdin","r");
        $username = fgets($handle);
        print "Please enter password:";
        $handle = fopen ("php://stdin","r");
        $username = fgets($handle);
        print "Created new user ".$username;
    }

    /**
     * Set a user's password
     *
     * @return void
     */
    public function destroy() {
        print "Please enter new password:";
    }

    /**
     * Disable or enable foreign key check
     * @var boolean
     */
    protected static $foreignKeyCheck = false;

    /**
     * Run the command
     *
     * @return void
     */
    public function run()
    {
        $command = $this->argument(0);
        if ($command == 'add') {
            $this->adduser();
        }
        elseif ($command == "setpassword")
        {
            $username = $this->argument(1);
            $this->setpassword($username);
        }
        else
            $this->output(static::AVAILABLE_COMMANDS);

        if (!static::$foreignKeyCheck)
            SQL::query("SET FOREIGN_KEY_CHECKS=1");
    }
}