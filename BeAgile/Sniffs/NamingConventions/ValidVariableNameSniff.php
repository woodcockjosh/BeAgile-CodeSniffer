<?php
/**
 * BeAgile_Sniffs_NamingConventions_ValidVariableNameSniff.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006-2011 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

if (class_exists('Squiz_Sniffs_NamingConventions_ValidVariableNameSniff', true) === false) {
    $error = 'Class Sniffs_NamingConventions_ValidVariableNameSniff not found';
    throw new PHP_CodeSniffer_Exception($error);
}

/**
 * BeAgile_Sniffs_NamingConventions_ValidVariableNameSniff.
 *
 * Checks the naming of variables and member variables.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006-2011 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   Release: 1.3.2
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class BeAgile_Sniffs_NamingConventions_ValidVariableNameSniff extends Squiz_Sniffs_NamingConventions_ValidVariableNameSniff
{

    /**
     * Tokens to ignore so that we can find a DOUBLE_COLON.
     *
     * @var array
     */
    private $_ignore = array(
                        T_WHITESPACE,
                        T_COMMENT,
                       );
    /**
     * Processes class member variables.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    protected function processMemberVar(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $varName     = ltrim($tokens[$stackPtr]['content'], '$');
        $memberProps = $phpcsFile->getMemberProperties($stackPtr);
        if (empty($memberProps) === true) {
            // Couldn't get any info about this variable, which
            // generally means it is invalid or possibly has a parse
            // error. Any errors will be reported by the core, so
            // we can ignore it.
            return;
        }

        $public    = ($memberProps['scope'] !== 'private');
        $errorData = array($varName);

        if ($public === true) {

            if (substr($varName, 0, 1) === 'm') {

				if($this->typeAnnotated($varName))
				{
					$error = '%s member variable "%s" must not contain a leading "m"';
					$data  = array(
							  ucfirst($memberProps['scope']),
							  $errorData[0],
							 );
					$phpcsFile->addError($error, $stackPtr, 'PublicHasM', $data);
					return;
				}

            }

			if($this->typeAnnotated($varName))
			{
				$error = '%s member variable "%s" must not contain a type annotation: "str","int","dbl","bool","obj"';
				$data  = array(
						  ucfirst($memberProps['scope']),
						  $errorData[0],
						 );
				$phpcsFile->addError($error, $stackPtr, 'PublicHasType', $data);
				return;
			}

			if (PHP_CodeSniffer::isCamelCaps($varName, false, $public, false) === false) {
				$error = 'Variable "%s" is not in valid camel caps format';
				$phpcsFile->addError($error, $stackPtr, 'MemberNotCamelCaps', $errorData);
			}
        } else {
            if (substr($varName, 0, 1) !== 'm') {
                $error = 'Private member variable "%s" must contain a leading "m"';
                $phpcsFile->addError($error, $stackPtr, 'PrivateNoM', $errorData);
                return;
            }

			if($this->typeAnnotated($varName) == false)
			{
				$error = 'Private member variable "%s" must contain a type annotation: "str","int","dbl","bool","obj"';
                $phpcsFile->addError($error, $stackPtr, 'PrivateNoType', $errorData);
                return;
			}else{
				if($this->capsAfterTypeAnnotation($varName) == false)
				{
					$error = 'The first letter after the type annotation inf variable "%s" must be caps';
					$phpcsFile->addError($error, $stackPtr, 'MemberNotCapsAfterType', $errorData);
				}
			}

			if (PHP_CodeSniffer::isCamelCaps("_" . $varName, false, $public, false) === false) {
				$error = 'Variable "%s" is not in valid camel caps format';
				$phpcsFile->addError($error, $stackPtr, 'MemberNotCamelCaps', $errorData);
			}
        }

    }//end processMemberVar()

	private function typeAnnotated($varName)
	{
		$typeAnnotated = false;
		$startChar = 0;

		if (substr($varName, 0, 1) === 'm') {
			$startChar = 1;
		}

		switch(substr($varName, $startChar, 3))
		{
			case "str":
			case "int":
			case "dbl":
			case "bool":
			case "obj":
				$typeAnnotated = true;
				break;
			default:
		}
		if(substr($varName, $startChar, 4) == "bool")
		{
			$typeAnnotated = true;
		}

		return $typeAnnotated;
	}

	private function capsAfterTypeAnnotation($varName)
	{
		$startChar = 0;

		if (substr($varName, 0, 1) === 'm') {
			$startChar = 1;
		}

		switch(substr($varName, $startChar, 3))
		{
			case "str":
			case "int":
			case "dbl":
			case "bool":
			case "obj":
				if(strtoupper(substr($varName, $startChar + 3, 1)) == substr($varName, $startChar + 3, 1))
				{
					return true;
				}
				break;
			default:
		}
		if(substr($varName, $startChar, 4) == "bool")
		{
			if(strtoupper(substr($varName, $startChar + 4, 1)) == substr($varName, $startChar + 4, 1))
			{
				return true;
			}
		}

		return false;
	}


    /**
     * Processes the variable found within a double quoted string.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the double quoted
     *                                        string.
     *
     * @return void
     */
    protected function processVariableInString(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $phpReservedVars = array(
                            '_SERVER',
                            '_GET',
                            '_POST',
                            '_REQUEST',
                            '_SESSION',
                            '_ENV',
                            '_COOKIE',
                            '_FILES',
                            'GLOBALS',
                           );
        if (preg_match_all('|[^\\\]\${?([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)|', $tokens[$stackPtr]['content'], $matches) !== 0) {
            foreach ($matches[1] as $varName) {
                // If it's a php reserved var, then its ok.
                if (in_array($varName, $phpReservedVars) === true) {
                    continue;
                }

                // There is no way for us to know if the var is public or private,
                // so we have to ignore a leading underscore if there is one and just
                // check the main part of the variable name.
                $originalVarName = $varName;
                if (substr($varName, 0, 1) === 'm') {
                    if ($phpcsFile->hasCondition($stackPtr, array(T_CLASS, T_INTERFACE)) === true) {
                        $varName = substr($varName, 1);
                    }

                }

                if (PHP_CodeSniffer::isCamelCaps($varName, false, true, false) === false) {
                    $varName = $matches[0];
                    $error = 'Variable "%s" is not in valid camel caps format';
                    $data  = array($originalVarName);
                    $phpcsFile->addError($error, $stackPtr, 'StringNotCamelCaps', $data);

                }
            }
        }//end if

    }//end processVariableInString()

	    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    protected function processVariable(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens  = $phpcsFile->getTokens();
        $varName = ltrim($tokens[$stackPtr]['content'], '$');

        $phpReservedVars = array(
                            '_SERVER',
                            '_GET',
                            '_POST',
                            '_REQUEST',
                            '_SESSION',
                            '_ENV',
                            '_COOKIE',
                            '_FILES',
                            'GLOBALS',
                           );

        // If it's a php reserved var, then its ok.
        if (in_array($varName, $phpReservedVars) === true) {
            return;
        }

        $objOperator = $phpcsFile->findNext(array(T_WHITESPACE), ($stackPtr + 1), null, true);
        if ($tokens[$objOperator]['code'] === T_OBJECT_OPERATOR) {
            // Check to see if we are using a variable from an object.
            $var = $phpcsFile->findNext(array(T_WHITESPACE), ($objOperator + 1), null, true);
            if ($tokens[$var]['code'] === T_STRING) {
                $bracket = $objOperator = $phpcsFile->findNext(array(T_WHITESPACE), ($var + 1), null, true);
                if ($tokens[$bracket]['code'] !== T_OPEN_PARENTHESIS) {
                    $objVarName = $tokens[$var]['content'];

                    // There is no way for us to know if the var is public or private,
                    // so we have to ignore a leading underscore if there is one and just
                    // check the main part of the variable name.
                    $originalVarName = $objVarName;
                    if (substr($objVarName, 0, 1) === '_') {
                        $objVarName = substr($objVarName, 1);
                    }

                    if (PHP_CodeSniffer::isCamelCaps($objVarName, false, true, false) === false) {
                        $error = 'Variable "%s" is not in valid camel caps format';
                        $data  = array($originalVarName);
                        //$phpcsFile->addError($error, $var, 'NotCamelCaps', $data);
                    }
                }//end if
            }//end if
        }//end if

        // There is no way for us to know if the var is public or private,
        // so we have to ignore a leading underscore if there is one and just
        // check the main part of the variable name.
        $originalVarName = $varName;
        if (substr($varName, 0, 1) === '_') {
            $objOperator = $phpcsFile->findPrevious(array(T_WHITESPACE), ($stackPtr - 1), null, true);
            if ($tokens[$objOperator]['code'] === T_DOUBLE_COLON) {
                // The variable lives within a class, and is referenced like
                // this: MyClass::$_variable, so we don't know its scope.
                $inClass = true;
            } else {
                $inClass = $phpcsFile->hasCondition($stackPtr, array(T_CLASS, T_INTERFACE));
            }

            if ($inClass === true) {
                $varName = substr($varName, 1);
            }
        }

        if(substr($varName,0,1) == "l")
        {
        	switch(substr($varName,0,4))
        	{
        		case "lstr":
        		case "ldbl":
        		case "lint":
        		case "lobj":
        			break;
        		case "lboo":
        			if(substr($varName,0,5) != "lbool")
        			{
        				$error = 'Local variable "%s" must have a type annotation: "str","dbl","int","obj","bool"';
        				$data  = array($originalVarName);
        				$phpcsFile->addError($error, $stackPtr, 'HasNotType', $data);
        			}
        			break;
        		default:
        			$error = 'Local variable "%s" must have a type annotation: "str","dbl","int","obj","bool"';
        			$data  = array($originalVarName);
        			$phpcsFile->addError($error, $stackPtr, 'HasNotType', $data);

        	}
        }else{
        	if(substr($varName,0,4) != 'this')
        	{
        		$error = 'Local variable "%s" must begin with the letter "l"';
        		$data  = array($originalVarName);
        		$phpcsFile->addError($error, $stackPtr, 'HasNoL', $data);
        	}
        }



        if (PHP_CodeSniffer::isCamelCaps($varName, false, true, false) === false) {
            $error = 'Variable "%s" is not in valid camel caps format';
            $data  = array($originalVarName);
            $phpcsFile->addError($error, $stackPtr, 'NotCamelCaps', $data);
        }

    }//end processVariable()


}//end class

?>
