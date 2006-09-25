<?php
// vim: foldmethod=marker
/**
 *	Ethna_Plugin_Handle_UninstallPlugin.php
 *
 *  @author     ICHII Takashi <ichii386@schweetheart.jp>
 *	@license	http://www.opensource.org/licenses/bsd-license.php The BSD License
 *	@package	Ethna
 *	@version	$Id$
 */

include_once(ETHNA_BASE . '/class/Ethna_PearWrapper.php');

// {{{ Ethna_Plugin_Handle_UninstallPlugin
/**
 *  install-plugin handler
 *
 *  @author     ICHII Takashi <ichii386@schweetheart.jp>
 *	@access		public
 *	@package	Ethna
 */
class Ethna_Plugin_Handle_UninstallPlugin extends Ethna_Plugin_Handle
{
    // {{{ _parseArgList()
    /**
     * @access private
     */
    function &_parseArgList()
    {
        $r =& $this->_getopt(array('local', 'master', 'basedir=', 'channel='));
        if (Ethna::isError($r)) {
            return $r;
        }
        list($opt_list, $arg_list) = $r;

        $ret = array();

        // options
        foreach ($opt_list as $opt) {
            switch (true) {
                case ($opt[0] == 'l' || $opt[0] == '--local'):
                    $ret['target'] = 'local';
                    break;

                case ($opt[0] == 'm' || $opt[0] == '--master'):
                    $ret['target'] = 'master';
                    break;

                case ($opt[0] == 'b' || $opt[0] == '--basedir'):
                    $ret['basedir'] = $opt[1];
                    break;

                case ($opt[0] == 'c' || $opt[0] == '--channel'):
                    $ret['channel'] = $opt[1];
                    break;
            }
        }

        // arguments
        if (count($arg_list) == 2) {
            $ret['type'] = $arg_list[0];
            $ret['name'] = $arg_list[1];
        }

        return $ret;
    }
    // }}}

    // {{{ perform()
    /**
     *  @access public
     */
    function perform()
    {
        $args =& $this->_parseArgList();
        if (Ethna::isError($args)) {
            return $args;
        }
        $pear =& new Ethna_PearWrapper();

        if (isset($args['type']) && isset($args['name'])) {
            // install from repository.
            $target = isset($args['target']) ? $args['target'] : null;
            $channel = isset($args['channel']) ? $args['channel'] : null;
            $basedir = isset($args['basedir']) ? realpath($args['basedir']) : getcwd();
            if ($target == 'master') {
                $pkg_name = sprintf('Ethna_Plugin_%s_%s', $args['type'], $args['name']);
            } else {
                $pkg_name = sprintf('Skel_Plugin_%s_%s', $args['type'], $args['name']);
            }

            $r =& $pear->init($target, $basedir, $channel);
            if (Ethna::isError($r)) {
                return $r;
            }
            $pkg_name = $pear->getCanonicalPackageName($pkg_name);
            if (Ethna::isError($pkg_name)) {
                return $pkg_name;
            }
            $r =& $pear->doUninstall($pkg_name);
            if (Ethna::isError($r)) {
                return $r;
            }

        } else {
            return Ethna::raiseError('invalid number of arguments', 'usage');
        }

        if ($target != 'master') {
            list(,, $ctype, $cname) = explode('_', $pkg_name, 4);
            $ok = $pear->confirmDialog('delete plugin generated from skelton? (could delete locally modified files)');
            if ($ok) {
                $generator =& new Ethna_Generator();
                // TODO: deal with the package including some plugins.
                $r = $generator->remove('Plugin', $ctype, $cname, $basedir);
                if (Ethna::isError($r)) {
                    return $r;
                }
            }
        }

        return true;
    }
    // }}}

    // {{{ getDescription()
    /**
     *  @access public
     */
    function getDescription()
    {
        return <<<EOS
uninstall plugin:
    {$this->id} [-c|--channel=channel] [-b|--basedir=dir] [-l|--local] [-m|--master] [type name]

EOS;
    }
    // }}}

    // {{{
    /**
     *  @access public
     */
    function getUsage()
    {
        return <<<EOS
ethna {$this->id} [-c|--channel=channel] [-b|--basedir=dir] [-l|--local] [-m|--master] [type name]
EOS;
    }
    // }}}
}
// }}}
?>