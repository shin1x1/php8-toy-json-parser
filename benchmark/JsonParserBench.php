<?php

use Shin1x1\ToyJsonParser\JsonParser;

final class JsonParserBench
{
    public function bench_JsonParser()
    {
        $sut = new JsonParser();
        $sut->parse($this->getJson());
    }

    public function bench_json_decode()
    {
        json_decode($this->getJson(), associative: true);
    }

    private function getJson(): string
    {
        return '
        {
  "glossary": {
    "title": "example glossary",
    "GlossDiv": {
      "title": "S",
      "GlossList": {
        "GlossEntry": {
          "ID": "SGML",
          "SortAs": "SGML",
          "GlossTerm": "Standard Generalized Markup Language",
          "Acronym": "SGML",
          "Abbrev": "ISO 8879:1986",
          "GlossDef": {
            "para": "A meta-markup language, used to create markup languages such as DocBook.",
            "GlossSeeAlso": ["GML", "XML"]
          },
          "GlossSee": "markup"
        }
      }
    }
  }
}
        ';
    }
}
