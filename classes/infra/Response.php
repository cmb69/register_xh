<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

class Response
{
    /** @var string */
    private $output = "";

    /** @var array<string,mixed> */
    private $meta = [];

    /** @var string|null */
    private $script = null;

    /** @var string|null */
    private $title = null;

    /** @var bool */
    private $forbidden = false;

    /** @var string|null */
    private $location = null;

    public function body(string $string): self
    {
        $this->output = $string;
        return $this;
    }

    /** @param mixed $data */
    public function addMeta(string $key, $data): self
    {
        $this->meta[$key] = $data;
        return $this;
    }

    public function addScript(string $filename): self
    {
        $this->script = $filename;
        return $this;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function forbid(): self
    {
        $this->forbidden = true;
        return $this;
    }

    public function redirect(string $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function output(): string
    {
        return $this->output;
    }

    /** @return array<string,mixed> */
    public function meta(): array
    {
        return $this->meta;
    }

    public function script(): ?string
    {
        return $this->script;
    }

    public function title(): ?string
    {
        return $this->title;
    }

    public function forbidden(): bool
    {
        return $this->forbidden;
    }

    public function location(): ?string
    {
        return $this->location;
    }

    /** @return string|never */
    public function fire()
    {
        global $hjs, $title;

        if ($this->location !== null) {
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Location: ' . $this->location);
            echo $this->output;
            exit;
        }
        if ($this->forbidden) {
            header("HTTP/1.1 403 Forbidden");
        }
        foreach ($this->meta as $key => $data) {
            $key = XH_hsc($key);
            $content = (string) json_encode($data, JSON_HEX_APOS | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $hjs .= "\n<meta name=\"$key\" content='$content'>";
        }
        if ($this->script !== null) {
            $filename = XH_hsc($this->script);
            $hjs .= "\n<script src=\"$filename\"></script>";
        }
        if ($this->title !== null) {
            $title = XH_hsc($this->title);
        }
        return $this->output;
    }
}
