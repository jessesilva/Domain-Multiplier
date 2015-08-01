<?php
	
	error_reporting(0);
	
	/* 
	** Inicializa aplicação. 
	*/
	if (($application = new Application()) != null && $application->status() === true) {
		$application->startup();
		$application->close();
	} else 
		die("Error to create class Application.\n");
	
	/*
	** Classe de controle da aplicação.
	*/
	class Application {
		private $flag;
		private $domainList;
		private $outputFullURLFile;
		private $outputDomainFile;
		private $counter;
		private $step;
		
		/* Inicializa classe. */
		public function __construct() {
			$this->zeroClass();
			$this->flag = true;
			$this->outputFullURLFile = 'list-full-urls.txt';
			$this->outputDomainFile = 'list-domains.txt';
		}
		
		/* Retorna estado da classe. */
		public function status() {
			return $this->flag;
		}
		
		/* Núcleo da classe. */
		public function startup() {
			$IPv4 = gethostbyname($_SERVER['argv'][2]);
			if ($this->check($IPv4) === true)
				$this->bing("ip:{$IPv4}");
		}
		
		/*
		** Faz busca em todas páginas do Bing.
		** @param $dork Query de busca.
		*/
		private function bing($dork) {
			for ($page = 0; ; $page++) {
				if (($sock = fsockopen('www.bing.com', 80, $e, $err, 3)) === false)
					continue;
				
				$header  = "GET /search?q={$dork}&first={$page}1&FORM=PERE HTTP/1.1\r\n";
				$header .= "User-Agent: Mozilla/5.0 (Windows NT 6.1; Trident/7.0; SLCC2; ";
				$header .= ".NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; ";
				$header .= "Media Center PC 6.0; .NET4.0C; .NET4.0E; rv:11.0) like Gecko\r\n";
				$header .= "Host: www.bing.com\r\n";
				$header .= "Connection: close\r\n\r\n";
				
				fputs($sock, $header);
				$response = "";
				while (!feof($sock)) 
					$response .= fread($sock, 8192);
				fclose($sock);
				
				if ($this->check($response) === true) {
					preg_match_all('!https?://\S+!', $response, $matches);
					$urls = $matches[0];

					for ($index = 0; $this->check($urls[$index]) === true; $index++) {
						$url = explode("://", $urls[$index]);
						$url = explode("\"", $url[1]);
						$url = explode("/", $url[0]);
						if ($this->filter($url[0]) === true) {
							if (!strstr($url[0], "<") && strstr($url[0], ".") && strlen($url[0]) > 3 &&
								!strstr($url[0], "}") && !strstr($url[0], "{") ) {
								$this->showInformation($url[0]);
								$this->save($urls[$index], $url[0]);
							}
						}
					}
				} else 
					break;
				
				if (!strstr($response, 'class="sb_pagN"'))
					break;
			}
		}
		
		/* Encerra aplicação. */
		public function close() {
			$this->zeroClass();
			if ($_SERVER['argc'] == 2)
				die("\n Application finished.\n");
			exit();
		}
		
		/* 
		** Verifica se existem dados em variável. 
		** @param $buffer Dados a serem analisados.
		** @return 		  Se existirem dados retorna 'true', caso contrário 'false'.
		*/
		private function check($buffer) {
			if ($buffer) {
				if (!empty($buffer))
					if (strlen($buffer) > 0)
						if ($buffer != "")
							return true;
			}
			return false;
		}
		
		/* Zera atributos da classe. */
		private function zeroClass() {
			$this->flag = false;
			$this->domainList = null;
			$this->outputFullURLFile = null;
			$this->outputDomainFile = null;
			$this->counter = 0;
			$this->step = 0;
		}
		
		/* 
		** Exibe informações no terminal.
		** @param $buffer Dados a serem exibidos.
		*/
		private function showInformation($buffer) {
			if ($this->step == 0) {
				$this->step = 1;
			}
			
			print " [{$_SERVER['argv'][1]}.{$this->counter}] -> {$buffer} extracted!\n";
			$this->counter++;
		}
		
		/* 
		** Verifica se URL não está na blacklist.
		** @param $buffer Dados a serem salvos.
		** @return 		  Se for uma URL válida retorna 'true', caso contrário 'false'.
		*/
		private function filter($buffer) {
			if ($this->check($buffer) === false)
				return false;
			
			$strings = array(
				"microsoft.com", "msn.com", "w3.org", "live.com", "microsofttranslator.com",
				"sandyclough.com", "bingj.com", "http://\"+_d.domain+\"/\"", "google.com", null);
			
			for ($index = 0; $strings[$index] !== null; $index++) 
				if (strstr($buffer, $strings[$index])) 
					return false;
			
			$status = false;
			if (($fp = fopen($this->outputDomainFile, 'r')) !== false) {
				while (!feof($fp)) {
					if (strstr(fgets($fp), $buffer)) {
						$status = true; break;
					}
				}
				fclose($fp);
			}
			
			if ($status === true)
				return false;
			
			return true;
		}
		
		/* 
		** Salva dados em arquivos de saída.
		** @param $fullUrl 	URL completa.
		** @param $domain 	Domínio.
		*/
		private function save($fullUrl, $domain) {
			$fullUrlContent = explode("\"", $fullUrl);
			if (($fp = fopen($this->outputFullURLFile, 'a+')) !== false) {
				fwrite($fp, $fullUrlContent[0] . "\n");
				fclose($fp);
			}
			if (($fp = fopen($this->outputDomainFile, 'a+')) !== false) {
				fwrite($fp, $domain . "\n");
				fclose($fp);
			}
		}
	}
	
?>