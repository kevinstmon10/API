<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * Sensors
 *
 * @ORM\Table(name="sensors", indexes={@ORM\Index(name="sensors_ibfk_1", columns={"StationId"})})
 * @ORM\Entity
 */
class Sensors implements \JsonSerializable {

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="Name", type="string", length=250, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="Status", type="string", length=250, nullable=false)
     */
    private $status;

    /**
     * @var float|null
     *
     * @ORM\Column(name="Min_Value", type="float", precision=10, scale=0, nullable=true, options={"default"="NULL"})
     */
    private $minValue = 'NULL';

    /**
     * @var float|null
     *
     * @ORM\Column(name="Max_Value", type="float", precision=10, scale=0, nullable=true, options={"default"="NULL"})
     */
    private $maxValue = 'NULL';

    /**
     * @var \Stations
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Stations", inversedBy="Stations")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="StationId", referencedColumnName="id")
     * })
     */
    private $stationid;


    public function getId(): ?int {
        return $this->id;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function setName(string $name): self {
        $this->name = $name;

        return $this;
    }

    public function getStatus(): ?string {
        return $this->status;
    }

    public function setStatus(string $status): self {
        $this->status = $status;

        return $this;
    }

    public function getMinValue(): ?float {
        return $this->minValue;
    }

    public function setMinValue(?float $minValue): self {
        $this->minValue = $minValue;

        return $this;
    }

    public function getMaxValue(): ?float {
        return $this->maxValue;
    }

    public function setMaxValue(?float $maxValue): self {
        $this->maxValue = $maxValue;

        return $this;
    }

    public function getStationid(): ?Stations {
        return $this->stationid;
    }

    public function setStationid(?Stations $stationid): self {
        $this->stationid = $stationid;

        return $this;
    }

     public function jsonSerialize(): array{
         return [
             'id' => $this->id,
             'name' => $this->name,
             'status' => $this->status,
             'minValue' => $this->minValue,
             'maxValue' => $this->maxValue
             
         ];
    }
}
