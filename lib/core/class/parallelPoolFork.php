<?php
/**
 * @author Jenner <hypxm@qq.com>
 * @blog http://www.huyanping.cn
 * @license https://opensource.org/licenses/MIT MIT
 * @datetime: 2015/11/19 20:49
 *
 * @author Daniel Gomes
 * Adaptation pour la prise en charge du nombre de process à exécuter
 * Les intervals de temps entre chaque process
 * et ajout de la possibilité d'exécuter des méthodes avant et après le traitement des process
 */

namespace Jenner\SimpleFork;

/**
 * parallel pool
 *
 * @package Jenner\SimpleFork
 */
class parallelPoolFork extends AbstractPool
{

    /**
     * @var callable|Runnable sub process callback
     */
    protected $runnable;

    /**
     * @var int $nbProcessLimit premet de limiter le nombre de process à exécuter (valeur initiale)
     */
    protected $nbProcessLimit;

    /**
     * @var int $nbProcessLimit premet de limiter le nombre de process à exécuter (décompte)
     */
    protected $nbProcessLimitVar;

    /**
     * @var int max process count
     */
    protected $max;

    /**
     * @var int $intervalProcess interval en mirco-secondes entre chaque création de process
     */
    protected $intervalProcess;

    /**
     * @var array méthodes à exécuter avant la méthode 'start'
     */
    protected $beforeStart = array();

    /**
     * @var array méthodes à exécuter avant de quitter
     */
    protected $beforeExit = array();


    /**
     * @param callable|Runnable $callback
     * @param int $max
     */
    public function __construct($callback, $max = 4)
    {
        if (!is_callable($callback) && !($callback instanceof Runnable)) {
            $message = "callback must be a callback function or a object of Runnalbe";
            throw new \InvalidArgumentException($message);
        }

        $this->runnable = $callback;
        $this->max = $max;

        $this->setIntervalProcess();
        $this->setNbProcessLimit();
    }


    /**
     * Interval entre chaque lancement de process
     *
     * @param   int     $interval   interval en mirco-secondes
     */
    public function setIntervalProcess($interval = 500000)
    {
        $this->intervalProcess = $interval;
    }


    /**
     * Permet de limiter le nombre de process à exécuter
     *
     * @param   int     $interval   interval en mirco-secondes
     */
    public function setNbProcessLimit($nbProcessLimit = null)
    {
        $this->nbProcessLimit    = $nbProcessLimit;
        $this->nbProcessLimitVar = $nbProcessLimit;
    }


    /**
     * Permet d'exécution d'une ou plusieurs méthode
     * avant le traitement des process
     *
     * @param string $class     Nom de la classe
     * @param string $method    Nom de la méthode
     * @param string $type      Type de la méthode (normal|static)
     */
    public function setBeforeStart($class, $method, $type='normal')
    {
        if (is_callable(array($class, $method))) {
            $this->beforeStart[] = array('class'=>$class, 'method'=>$method, 'type'=>$type);
        }
    }


    /**
     * Permet d'exécution d'une ou plusieurs méthode
     * à la fin du traitement des process
     *
     * @param string $class     Nom de la classe
     * @param string $method    Nom de la méthode
     * @param string $type      Type de la méthode (normal|static)
     */
    public function setBeforeExit($class, $method, $type='normal')
    {
        if (is_callable(array($class, $method))) {
            $this->beforeExit[] = array('class'=>$class, 'method'=>$method, 'type'=>$type);
        }
    }


    /**
     * Traitement des méthodes demandées avant la méthode 'start'
     */
    private function beforeStart()
    {
        if (count($this->beforeStart) > 0) {

            foreach ($this->beforeStart as $action) {
                $class  = $action['class'];
                $method = $action['method'];

                if ($action['type'] == 'normal') {
                    $class = new $class();
                    $class->$method();  // méthode normale
                } else {
                    $class::$method();  // méthode classic
                }
            }

            $this->beforeStart = array();
        }
    }


    /**
     * Traitement des méthodes demandées avant de quitter
     */
    private function beforeExit()
    {
        if (count($this->beforeExit) > 0) {

            foreach ($this->beforeExit as $action) {
                $class  = $action['class'];
                $method = $action['method'];

                if ($action['type'] == 'normal') {
                    $class = new $class();
                    $class->$method();  // méthode normale
                } else {
                    $class::$method();  // méthode classic
                }
            }
        }
    }


    /**
     * start the pool
     */
    public function start()
    {
        // Traitement des méthodes demandées avant la méthode 'start'
        $this->beforeStart();

        $alive_count = $this->aliveCount();

        // create sub process and run
        if ($alive_count < $this->max) {

            $need = $this->max - $alive_count;

            for ($i = 0; $i < $need; $i++) {

                if (! is_null($this->nbProcessLimit) && $this->nbProcessLimitVar >= 0) {
                    $this->nbProcessLimitVar--;
                }

                if (is_null($this->nbProcessLimit) || $this->nbProcessLimitVar >= 0) {

                    $process = new Process($this->runnable);
                    $process->start();
                    $this->processes[$process->getPid()] = $process;

                    usleep($this->intervalProcess);

                } else {

                    if ($this->count() > 0) {

                        // 1 ou plusieurs process sont encore en cours d'exécution
                        sleep(1);

                    } else {

                        // Tous les process sont terminés
                        if ($this->nbProcessLimitVar < 0) {

                            // Traitement des méthodes demandées avant de quitter
                            $this->beforeExit();

                            exit();
                        }
                    }
                }
            }
        }
    }


    /**
     * start the same number processes and kill the old sub process
     * just like nginx -s reload
     * this method will block until all the old process exit;
     *
     * @param bool $block
     */
    public function reload($block = true)
    {
        $old_process = $this->processes;

        foreach ($old_process as $process) {
            $process->shutdown();
            $process->wait($block);
            unset($this->processes[$process->getPid()]);
        }

        for ($i = 0; $i < $this->max; $i++) {

            if (! is_null($this->nbProcessLimit) && $this->nbProcessLimitVar >= 0) {
                $this->nbProcessLimit--;
            }

            if (is_null($this->nbProcessLimit) || $this->nbProcessLimitVar >= 0) {

                $process = new Process($this->runnable);
                $process->start();
                $this->processes[$process->getPid()] = $process;

                usleep($this->intervalProcess);

            } else {

                if ($this->count() > 0) {

                    // 1 ou plusieurs process sont encore en cours d'exécution
                    sleep(1);

                } else {

                    // Tous les process sont terminés
                    if ($this->nbProcessLimitVar < 0) {

                        // Traitement des méthodes demandées avant de quitter
                        $this->beforeExit();

                        exit();
                    }
                }
            }
        }
    }

    /**
     * keep sub process count
     *
     * @param bool $block block the master process
     * to keep the sub process count all the time
     * @param int $interval check time interval
     */
    public function keep($block = false, $interval = 100)
    {
        do {
            $this->start();

            // recycle sub process and delete the processes
            // which are not running from process list
            foreach ($this->processes as $process) {
                if (!$process->isRunning()) {
                    unset($this->processes[$process->getPid()]);
                }
            }

            $block ? usleep($interval) : null;
        } while ($block);
    }


    /**
     * return process count
     *
     * @return int
     */
    public function count()
    {
        return count($this->processes);
    }


    /**
     * get all processes
     *
     * @return Process[]
     */
    public function getProcesses()
    {
        return $this->processes;
    }
}
