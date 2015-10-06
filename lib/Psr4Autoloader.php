<?php

namespace Mx\Tools;

class Psr4Autoloader
{
    private static $instance;

    /**
     * ������������� ������. ����� �������� ������� ������������ ���, �������� � ������ ������� ���������� ��� �������
     * � ���� ������������ ���.
     *
     * @var array
     */
    protected $prefixes = array();

    /**
     * ������������ ��������� � ����� ����������� SPL.
     *
     * @return void
     */
    public function register()
    {
        spl_autoload_register(array($this, 'loadClass'));
    }

    /**
     * ��������� ������� ���������� � �������� ������������ ���.
     *
     * @param string $prefix ������� ������������ ���.
     * @param string $base_dir ������� ���������� ��� ������ ������� �� ������������ ���.
     * @param bool $prepend ���� true, �������� ������� ���������� � ������ �����. � ���� ������ ��� �����
     * ����������� ������.
     * @return void
     */
    public function addNamespace($prefix, $base_dir, $prepend = false)
    {
        // ����������� ������� ������������ ���
        $prefix = trim($prefix, '\\') . '\\';

        // ����������� ������� ���������� ���, ����� ������ ������������� ����������� � �����
        $base_dir = rtrim($base_dir, DIRECTORY_SEPARATOR) . '/';

        // �������������� ������ ��������� ������������ ���
        if (isset($this->prefixes[$prefix]) === false) {
            $this->prefixes[$prefix] = array();
        }

        // ��������� ������� ���������� ��� �������� ������������ ���
        if ($prepend) {
            array_unshift($this->prefixes[$prefix], $base_dir);
        } else {
            array_push($this->prefixes[$prefix], $base_dir);
        }
    }

    /**
     * ��������� ���� ��� ��������� ����� ������.
     *
     * @param string $class ���������� ��� ������.
     * @return mixed ���� ����������, ������ ��� �����. ����� false.
     */
    public function loadClass($class)
    {
        // ������� ������� ������������ ���
        $prefix = $class;

        // ��� ����������� ����� ����� ������� ������������ ��� �� �����������
        // ����� ������ � �������� �������

        while (false !== $pos = strrpos($prefix, '\\')) {

            // ��������� ����������� ����������� ������������ ��� � ��������
            $prefix = substr($class, 0, $pos + 1);

            // �� ���������� � ������������� ��� ������
            $relative_class = substr($class, $pos + 1);

            // ������� ��������� �������������� �������� � �������������� ����� ������ ����
            $mapped_file = $this->loadMappedFile($prefix, $relative_class);
            if ($mapped_file) {
                return $mapped_file;
            }

            // ������� ����������� ����������� ������������ ��� ��� ��������� �������� strrpos()
            $prefix = rtrim($prefix, '\\');
        }


        // ���� ��� � �� ��� ������
        return false;
    }

    /**
     * ��������� ��������������� �������� ������������ ��� � �������������� ����� ������ ����.
     *
     * @param string $prefix ������� ������������ ���.
     * @param string $relative_class ������������� ��� ������.
     * @return mixed false ���� ���� �� ��� ��������. ����� ��� ������������ �����.
     */
    protected function loadMappedFile($prefix, $relative_class)
    {
        // ���� �� � ����� �������� ������������ ��� ����� ���� ������� ����������?
        if (isset($this->prefixes[$prefix]) === false) {
            return false;
        }

        // ���� ������� � ������� �����������
        foreach ($this->prefixes[$prefix] as $base_dir) {

            // �������� ������� ������� �����������,
            // �������� ����������� ������������ ��� �� ����������� ����������
            // � �������������� ����� ������ ��������� .php
            $file = $base_dir
                . str_replace('\\', '/', $relative_class)
                . '.php';

            // ���� ���� ����������, ��������� ���
            if ($this->requireFile($file)) {
                // ���, ����������
                return $file;
            }
        }

        // ���� ��� � �� ��� ������
        return false;
    }

    /**
     * ���� ���� ����������, ���������� ���.
     *
     * @param string $file ���� ��� ��������.
     * @return bool true ���� ���� ����������, false ���� ���.
     */
    protected function requireFile($file)
    {
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
        return false;
    }

    /**
     * ������������� ������ ���� ���� ������������ � ������ ����� ������ � ������
     * @return Psr4Autoloader
     */
    public static function getInstance()
    {
        if (!self::$instance)
        {
            self::$instance = new self;
        }

        return self::$instance;
    }

    private function __wakeup(){}
    private function __clone(){}
    private function __create(){}
}