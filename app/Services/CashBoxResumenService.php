<?php

namespace App\Services;


use App\Service\GetCashBoxOpenedService;

class CashBoxResumenService
{
    public float $ingreso_factura;
    public float $ingreso_ccf;
    public float $ingreso_ordenes;
    public float $ingreso_taller;
    public float $ingreso_caja_chica;
    public float $ingreso_total;

    public float $egreso_caja_chica;
    public float $egreso_nc;
    public float $egreso_total;

    public float $saldo_efectivo_ventas;
    public float $saldo_tarjeta;
    public float $saldo_cheques;
    public float $saldo_efectivo_ordenes;
    public float $saldo_caja_chica;
    public float $saldo_total;

    public function __construct()
    {
        $srv = new GetCashBoxOpenedService();

        $this->ingreso_factura = $srv->getTotal(false, false, 1);
        $this->ingreso_ccf = $srv->getTotal(false, false, 3);
        $ordenes=$srv->obtenerTotalesOrdenYManoObra();
        $this->ingreso_ordenes = $ordenes['total_ordenes'];
        $this->ingreso_taller = $ordenes['total_mano_obra'];
        $this->ingreso_caja_chica = $srv->minimalCashBoxTotal('Ingreso');
        $this->ingreso_total = $this->ingreso_factura + $this->ingreso_ccf + $this->ingreso_ordenes+$this->ingreso_taller + $this->ingreso_caja_chica;

        $this->egreso_caja_chica = $srv->minimalCashBoxTotal('Egreso');
        $this->egreso_nc = $srv->getTotal(false, false, 5);
        $this->egreso_total = $this->egreso_caja_chica + $this->egreso_nc;

        $this->saldo_efectivo_ventas = $srv->getTotal(false, false, null, [1]);
        $this->saldo_tarjeta = $srv->getTotal(false, false, null, [2, 3]);
        $this->saldo_cheques = $srv->getTotal(false, false, null, [4, 5]);
        $this->saldo_efectivo_ordenes = $srv->getTotal(true, true);
        $this->saldo_caja_chica = $srv->minimalCashBoxTotal('Ingreso');
        $this->saldo_total = $this->saldo_efectivo_ventas
            + $this->saldo_tarjeta
            + $this->saldo_cheques
            + $this->saldo_efectivo_ordenes
            + $this->saldo_caja_chica
            - $this->egreso_total;
    }
}
