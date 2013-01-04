<?php

/*
	Rules:
		-required (value is required)
		-required_if (value is required if other value is present)
		-equals (value ==)
		-match_regex
		-min_length
		-max_length
		-exact_length
		-min_val
		-max_val
		-is_numeric
		-is_int
		-is_float
		-valid_ip
		-valid_uri
		-valid_email
		-valid_emails
		-custom

	Syntax:
		$validation->register('fieldname', 'Readable Name')
			->rule('required')
			->rule('min_length', 5)
			->rule('max_length', 20)
			->rule('custom', 'Some_Class::some_custom_callback')
			...
*/


class Validation
{
	const Static_Callback_Delimeter = '::';

	private $reflection;
	private $rules = array();
	private $readable = array();
	private $last;

	private $rule_phrases = array(
		'required'		=> '%s is required',
		'required_if'	=> '%s is required if %s is present',
		'equals'		=> '%s must equal %s',
		'match_regex'	=> '%s must match pattern %s',
		'min_length'	=> '%s must be at least %s characters long',
		'max_length'	=> '%s must be less than %s characters',
		'exact_length'	=> '%s must be exactly %s characters',
		'min_val'		=> '%s must be at least %s',
		'max_val'		=> '%s must be less than %s',
		'is_numeric'	=> '%s must be a valid number',
		'is_int'		=> '%s must be a valid integer',
		'is_float'		=> '%s must be a valid decimal',
		'valid_ip'		=> '%s must be a valid IP address',
		'valid_uri'		=> '%s must be a valid URL',
		'valid_email'	=> '%s is an invalid email address',
		'valid_emails'	=> '%s must contain a valid list of email addresses'
	);
	
	public $values = array();
	public $messages = array();




	public function __construct()
	{
		$this->reflection = new ReflectionClass($this);
	}


	public function register($key, $readable = null)
	{
		if(!array_key_exists($key, $this->rules))
		{
			$this->last = $key;
			$this->rules[$this->last] = array();
			$this->readable[$this->last] = $readable;
		}
		else
		{
			Logger::Log(sprintf("%s has already been registered for validation", $key), Log_Level::Warning);
		}

		return $this;
	}


	public function rule($rule, $param = null)
	{
		if($this->reflection->hasMethod($rule) && !is_null($this->last))
		{
			$this->rules[$this->last][$rule] = $param;
		}
		else
		{
			Logger::Log(sprintf("Rule '%s' does not exist, ignoring", $rule));
		}
		
		return $this;
	}


	public function validate()
	{
		if($this->form_submitted())
		{
			$valid = true;

			foreach($this->rules as $key => $rules)
			{
				$this->values[$key] = Input::post($key);
				
				foreach($rules as $rule => $param)
				{
					if(!$this->$rule($key, $param))
					{
						$valid = false;
						if($rule != 'custom')
						{
							$this->messages[$key] = sprintf($this->rule_phrases[$rule], $this->readable[$key], $param);
						}
						break;
					}
				}
			}
			return $valid;
		}
		return false;
	}


	public function message($key)
	{
		return isset($this->messages[$key]) ? $this->messages[$key] : '';
	}


	public function value($key)
	{
		return isset($this->values[$key]) ? $this->values[$key] : null;
	}


	private function form_submitted()
	{
		return isset($_POST) && sizeof($_POST) > 0;
	}


	private function is_present($key)
	{
		return isset($_POST[$key]) && !is_null($_POST[$key]) && strlen($_POST[$key]);
	}


	private function required($key)
	{
		return $this->is_present($key);
	}


	private function required_if($key, $dependent)
	{
		return $this->is_present($dependent) 
			? $this->is_present($key) 
			: true;
	}


	private function equals($key, $value)
	{
		return Input::post($key) == $value;
	}


	private function match_regex($key, $pattern)
	{
		return (preg_match($pattern, Input::post($key)) == 1)
			? true
			: false;
	}


	private function min_length($key, $length = 0)
	{
		return strlen(Input::post($key)) >= $length;
	}


	private function max_length($key, $length = PHP_INT_MAX)
	{
		return strlen(Input::post($key)) <= $length;
	}


	private function exact_length($key, $length)
	{
		return strlen(Input::post($key)) == $length;
	}


	private function min_val($key, $val)
	{
		return is_numeric(Input::post($key)) && (int)Input::post($key) >= (int)$val;
	}


	private function max_val($key, $val)
	{
		return is_numeric(Input::post($key)) && (int)Input::post($key) <= (int)$val;
	}


	private function is_numeric($key)
	{
		return is_numeric(Input::post($key));
	}


	private function is_int($key)
	{
		return is_int(Input::post($key));
	}


	private function is_float($key)
	{
		return is_float(Input::post($key));
	}


	private function valid_ip($key, $version = 'v4')
	{
		return $version == 'v6'
			? filter_var(Input::post($key), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
			: filter_var(Input::post($key), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
	}


	private function valid_uri($key)
	{
		return filter_var(Input::post($key), FILTER_VALIDATE_URL);
	}


	private function valid_email($key)
	{
		return filter_var(Input::post($key), FILTER_VALIDATE_EMAIL);
	}


	private function valid_emails($key)
	{
		// if newline exists in value, explode on newline, otherwise assume comma delimited
		$emails = explode((strpos(Input::post($key), "\n") !== false ? "\n" : ","), Input::post($key));

		if(!is_array($emails) || !sizeof($emails))
		{
			return false;
		}

		foreach($emails as $email)
		{
			if(!$this->valid_email($email))
			{
				return false;
			}
		}

		return true;
	}


	private function custom($key, $callback)
	{
		if(sizeof($call = explode(self::Static_Callback_Delimeter, $callback)) == 2 && method_exists($call[0], $call[1]))
		{
			$message = '';

			if(!call_user_func_array($callback, array(Input::post($key), &$message)))
			{
				$this->messages[$key] = $message;
				return false;
			}
			return true;
		}

		Logger::log(sprintf("Validation callback function %s was not found", $callback), Log_Level::Error);
		return false;
	}
}
