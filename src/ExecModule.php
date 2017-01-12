<?php

namespace Bluora\PhpElixir\Modules;

use Bluora\PhpElixir\AbstractModule;
use Bluora\PhpElixir\ElixirConsoleCommand as Elixir;

class ExecModule extends AbstractModule
{
    /**
     * Verify the configuration for this task.
     *
     * @param string $executable
     * @param string $arguments
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NpathComplexity)
     */
    public static function verify($executable, $arguments)
    {
        // We can't execute a folder.
        if (is_dir($executable)) {
            Elixir::console()->error('Provided path is a folder.');

            return false;
        }

        $original_executable = $executable;

        if (($executable = self::getAbsolutePath($executable)) === false) {
            Elixir::console()->error(sprintf('Can not find executable %s.', $original_executable, $executable));

            return false;
        }

        // Check permissions to execute this file
        $permissions = fileperms($executable);
        $file_stat = stat($executable);
        $file_uid = posix_getuid();
        $file_gid = posix_getgid();

        $is_executable = false;

        if ($file_stat['uid'] == $file_uid) {
            $is_executable = (($permissions & 0x0040) ?
                (($permissions & 0x0800) ? true : true) :
                (($permissions & 0x0800) ? true : $is_executable));
        }

        if ($file_stat['gid'] == $file_gid) {
            $is_executable = (($permissions & 0x0008) ?
                (($permissions & 0x0400) ? true : true) :
                (($permissions & 0x0400) ? true : $is_executable));
        }

        $is_executable = (($permissions & 0x0001) ?
            (($permissions & 0x0200) ? true : true) :
            (($permissions & 0x0200) ? true : $is_executable));

        if (!$is_executable) {
            Elixir::console()->error(sprintf('Can not run %s %s', $original_executable, $arguments));
        }

        return $is_executable;
    }

    /**
     * Run the task.
     *
     * @param string $executable
     * @param string $arguments
     *
     * @return bool
     */
    public function run($executable, $arguments)
    {
        Elixir::commandInfo('Executing \'exec\' module...');
        Elixir::console()->line('');
        Elixir::console()->info('   Executing...');
        Elixir::console()->line(sprintf(' - %s %s', $executable, $arguments));
        Elixir::console()->line('');

        $executable = self::getAbsolutePath($executable);

        // Run the command, show output if verbose is on.
        if (!Elixir::dryRun()) {
            $run_function = (Elixir::verbose()) ? 'passthru' : 'exec';
            $arguments .= (!Elixir::verbose()) ? ' > /dev/null 2> /dev/null' : '';

            $run_function(sprintf('%s %s', $executable, $arguments));

            if (Elixir::verbose()) {
                Elixir::console()->line('');
            }
        }

        return true;
    }

    /**
     * Get absolute path for a executable file.
     *
     * @param string $path
     *
     * @return string|bool
     */
    private static function getAbsolutePath($path)
    {
        if (file_exists($path)) {
            return $path;
        }

        $path = trim(shell_exec(sprintf('which %s 2>&1', $path)));

        if (stripos($path, 'which: no') === false) {
            $path = trim(shell_exec(sprintf('readlink -f %s', $path)));

            return file_exists($path) ? $path : false;
        }

        return false;
    }
}
