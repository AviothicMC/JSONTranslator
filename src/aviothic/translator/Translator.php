<?php

namespace aviothic\translator;

use Webmozart\PathUtil\Path;

class Translator{
	/** @var array<string, array<string, string>> */
    private array $translations = [];
    private string $defaultLanguage = "en";
	private string $path;

    public static function init(string $path, ?string $defaultLanguage = null): self{
        $instance = new self();
		$instance->path = $path;
        if($defaultLanguage !== null){
			$instance->defaultLanguage = $defaultLanguage;
		}

        return $instance;
    }

    public function load(): void{
		$path = $this->path;
        $files = scandir($path);
		if($files === false){
			throw new \RuntimeException("Could not scan directory $path");
		}
        $files = array_filter($files, function(string $file) use($path): bool{
			return pathinfo($path . "/" . $file, PATHINFO_EXTENSION) === "json";
		});
        $languages = [];
        foreach($files as $file){
            $filePath = Path::join($path, $file);
			$file_contents = file_get_contents($filePath);
            if($file_contents === false){
				throw new \RuntimeException("Could not read file $filePath");
			}
            $translations = json_decode($file_contents, true, flags: JSON_THROW_ON_ERROR);
			if(!is_array($translations)){
				throw new \RuntimeException("Data must be a JSON object");
			}
            $languages[str_replace(".json", "", $file)] = $translations;
        }
        foreach($languages as $language => $translations){
			if(!isset($this->translations[$language])){
				$this->translations[$language] = [];
			}
			$this->translations[$language] = array_merge($this->translations[$language], $translations);
		}
    }

	public function translate(string $key, ?string $language = null, array $replacements = []): string{
		$language = $language ?? $this->defaultLanguage;
		if(isset($this->translations[$language][$key])){
			$translation = $this->translations[$language][$key];
		}elseif(isset($this->translations[$this->defaultLanguage][$key])){
			$translation = $this->translations[$this->defaultLanguage][$key];
		}else{
			return $key;
		}
		$translation = str_replace("&", "§", $translation);
		foreach($replacements as $placeholder => $value){
			$translation = str_replace("{%" . $placeholder . "}", strval($value), $translation);
		}

		return $translation;
	}

	public function getDefaultLanguage(): string{
		return $this->defaultLanguage;
	}

	public function getTranslations(): array{
		return $this->translations;
	}

	/**
	 * Adds a translation to the language cache and the corresponding JSON file.
	 *
	 * @param string $key
	 * @param string $value
	 * @param string $language
	 * @return void
	 * @throws \JsonException
	 */
	public function addTranslation(string $key, string $value, string $language): void{
		if(!isset($this->translations[$language])){
			$this->translations[$language] = [];
		}

		$this->translations[$language][$key] = $value;
		$filePath = Path::join($this->path, $language . ".json");
		$file_contents = file_get_contents($filePath);
		if($file_contents === false){
			throw new \RuntimeException("Could not read file $filePath");
		}
		$translations = json_decode($file_contents, true, flags: JSON_THROW_ON_ERROR);
		if(!is_array($translations)){
			throw new \RuntimeException("Data must be a JSON object");
		}

		$translations[$key] = $value;
		file_put_contents($filePath, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
	}
}
