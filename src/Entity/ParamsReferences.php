<?php
namespace App\Entity;

class ParamsReferences
{
    private $syntax;
    private $firstsequence;
    private $modelsequ;
    private $formanne;
    private $formalea;
    private $nbrcharal;
    private $sequence;

    // Getters et setters
    public function getSyntaxe() { return $this->syntax; }
    public function setSyntaxe($syntax) { $this->syntax = $syntax; }

    public function getFirstsequence() { return $this->firstsequence; }
    public function setFirstsequence($firstsequence) { $this->firstsequence = $firstsequence; }

    public function getModelsequ() { return $this->modelsequ; }
    public function setModelsequ($modelsequ) { $this->modelsequ = $modelsequ; }

    public function getFormanne() { return $this->formanne; }
    public function setFormanne($formanne) { $this->formanne = $formanne; }

    public function getFormalea() { return $this->formalea; }
    public function setFormalea($formalea) { $this->formalea = $formalea; }

    public function getNbrcharal() { return $this->nbrcharal; }
    public function setNbrcharal($nbrcharal) { $this->nbrcharal = $nbrcharal; }

    public function getSequence() { return $this->sequence; }
    public function setSequence($sequence) { $this->sequence = $sequence; }
}
