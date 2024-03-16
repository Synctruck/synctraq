import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'
import { Tooltip as ReactTooltip } from "react-tooltip";

function PaymentAdjustment() {

    const [teamName, setTeamName]   = useState(teamNameGeneral);
    const [paymentId, setPaymentId] = useState(paymentIdGeneral);
    const [numberTransaction, setNumberTransaction] = useState(numberTransactionGeneral);
    const [paymentStatus, setPaymentStatus] = useState(paymentStatusGeneral);
    const [startDate, setStartDate] = useState(startDateGeneral);
    const [initDate, setInitDate] = useState(initDateGeneral);
    const [endDate, setEndDate]     = useState(endDateGeneral);

    const [paymentTeamDetailRouteList, setPaymentTeamDetailRouteList] = useState([]);
    const [totalPieces, setTotalPieces] = useState(0);
    const [totalAverageCost, setTotalAverageCost] = useState(0);
    const [totalRoute, setTotalRoute] = useState(0);

    const [paymentTeamDetailPODFailedList, setPaymentTeamDetailPODFailedList] = useState([]);
    const [Reference_Number_1_POD_Failed, setReference_Number_1_POD_Failed] = useState('');

    const[paymentTeamDetailRevertShipmentsList, setPaymentTeamDetailRevertShipmentsList] = useState([]);

    const [totalInvoice, setTotalInvoice]   = useState(0.0000);
    const [amount, setAmount]               = useState('');
    const [description, setDescription]     = useState('');

    useEffect(() => {

        listByRoute(idPaymentGeneral);
        listByPODFailed(idPaymentGeneral);
        listRevertShipments(idPaymentGeneral);
        ListAdjustmentPayment(idPaymentGeneral);
    }, []);

    const listByRoute = (idPayment) => {

        fetch(url_general +'payment-team/list-by-route/'+ idPayment)
        .then(res => res.json())
        .then((response) => {

            setPaymentTeamDetailRouteList(response.paymentTeamDetailRouteList);
            setTotalDeduction(response.totalDeduction.totalDeduction);
            calculateTotalsDeliveries(response.paymentTeamDetailRouteList)
        });
    }

    const calculateTotalsDeliveries = (paymentTeamDetailRouteList) => {

        let auxTotalPieces      = 0;
        let auxTotalRoute       = 0;

        paymentTeamDetailRouteList.map((paymentDetailRoute) => {

            auxTotalPieces = parseInt(auxTotalPieces) + parseInt(paymentDetailRoute.totalPieces);
            auxTotalRoute  = parseFloat(auxTotalRoute) + parseFloat(paymentDetailRoute.totalRoute);
        });

        let auxTotalAverageCost = auxTotalRoute / auxTotalPieces;

        setTotalPieces(auxTotalPieces);
        setTotalRoute(auxTotalRoute.toFixed(3));
        setTotalAverageCost(auxTotalAverageCost.toFixed(3));
    }

    const handlerSaveAdjustment = (e) => {

        LoadingShowMap();

        e.preventDefault();

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const formData = new FormData();

        formData.append('idPaymentTeam', paymentId);
        formData.append('amount', amount);
        formData.append('description', description);

        fetch(url_general +'payment-team-adjustment/insert', {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json())
        .then((response) => {

                if(response.statusCode == true)
                {
                    swal("Adjustment was registered!", {

                        icon: "success",
                    });

                    clearFormAdjustment();
                    ListAdjustmentPayment(paymentId);
                }
                else if(response.statusCode == false)
                {
                    swal("a problem occurred, please try again!", {

                        icon: "error",
                    });
                }

                LoadingHideMap();
            },
        );
    }

    const clearFormAdjustment = () => {

        setAmount('');
        setDescription('');
    }

    const handlerExportPayment = (id) => {

        location.href = url_general +'payment-team/export/'+ id;
    }

    const handlerExportPaymentReceipt = (id) => {

        location.href = url_general +'payment-team/export-receipt/'+ id;
    }

    const listPaymentDetailRoute = paymentTeamDetailRouteList.map( (paymentDetail, i) => {

        return (

            <tr>
                <td>{ paymentDetail.Route }</td>
                <td className="text-right">{ paymentDetail.totalPieces }</td>
                <td className="text-right">$ { (paymentDetail.totalRoute / paymentDetail.totalPieces).toFixed(3) }</td>
                <td className="text-right">$ { paymentDetail.totalRoute }</td>
            </tr>
        );
    });

    const listByPODFailed = (idPayment) => {

        fetch(url_general +'payment-team/list-by-pod-failed/'+ idPayment)
        .then(res => res.json())
        .then((response) => {

            setPaymentTeamDetailPODFailedList(response.paymentTeamDetailPODFailedList);
        });
    }

    const handlerInserPDOFailed = (e) => {

        e.preventDefault();

        LoadingShowMap();

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const formData = new FormData();

        formData.append('Reference_Number_1', Reference_Number_1_POD_Failed);

        fetch(url_general +'payment-team/insert-pod-failed', {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json())
        .then((response) => {

                if(response.statusCode == true)
                {
                    swal('Correct!', 'Package marked as POD Failed #'+ Reference_Number_1_POD_Failed, 'success');

                    ListAdjustmentPayment(paymentId)
                }
                else
                {
                    swal("There was an error, try again!", {

                        icon: "error",
                    });
                }

                setReference_Number_1_POD_Failed('');

                listByRoute(paymentId);
                listByPODFailed(paymentId);
                LoadingHideMap();
            },
        );
    }

    const listPaymentDetailPODFailed = paymentTeamDetailPODFailedList.map( (paymentDetail, i) => {

        return (

            <tr>
                <td>{ paymentDetail.Reference_Number_1 }</td>
                <td className="text-right">$ 0.000</td>
            </tr>
        );
    });

    const [totalRevertShipment, setTotalRevertShipment] = useState(0);

    const listRevertShipments = (idPayment) => {

        fetch(url_general +'payment-team/list-revert-shipments/'+ idPayment)
        .then(res => res.json())
        .then((response) => {

            setPaymentTeamDetailRevertShipmentsList(response.paymentTeamDetailRevertShipmentsList);
            handlerCalculateTotalRevertShipments(response.paymentTeamDetailRevertShipmentsList);
        });
    }

    const handlerCalculateTotalRevertShipments = (listReverts) => {

        let total = 0;

        listReverts.map((revert, i) => {

            total = parseFloat(total) + parseFloat(revert.totalPrice);
        });

        setTotalRevertShipment(total.toFixed(3));
    }

    const listTableRevertShipments = paymentTeamDetailRevertShipmentsList.map( (paymentDetailReturn, i) => {

        return (

            <tr>
                <td>{ paymentDetailReturn.Reference_Number_1 }</td>
                <td>{ paymentDetailReturn.reason }</td>
                <td className="text-right">$ { paymentDetailReturn.totalPrice }</td>
            </tr>
        );
    });

    const [listAdjustment, setListAdjustment] = useState([]);
    const [totalAdjustment, setTotalAdjustment] = useState(0);
    const [totalDeduction, setTotalDeduction] = useState(0);

    const ListAdjustmentPayment = (idPayment) => {

        LoadingShowMap();

        fetch(url_general +'payment-team-adjustment/'+ idPayment)
        .then(response => response.json())
        .then(response => {

            LoadingHideMap();

            setListAdjustment(response.listAdjustment);
            handlerCalculateTotalAdjustment(response.listAdjustment);
        });
    }

    const handlerCalculateTotalAdjustment = (listAdjustment) => {

        let total = 0;

        listAdjustment.map((adjustment, i) => {

            total = parseFloat(total) + parseFloat(adjustment.amount);
        });

        setTotalAdjustment(total.toFixed(3));
    }

    const listTablePaymentAdjustment = listAdjustment.map( (adjustment, i) => {

        return (

            <tr>
                <td>{ adjustment.description }</td>
                <td><h6 className={ (adjustment.amount >= 0 ? 'text-success text-right' : 'text-danger text-right') }>{ adjustment.amount } $</h6></td>
            </tr>
        );
    });

    const calculateTotalInvoice = () => {

        let auxTotalInvoice = parseFloat(totalRoute) + parseFloat(totalRevertShipment) + parseFloat(totalAdjustment) + parseFloat(totalDeduction);

        setTotalInvoice(auxTotalInvoice.toFixed(3));
    }

    useEffect(() => {

        calculateTotalInvoice();

    }, [totalRoute, totalRevertShipment, totalAdjustment, totalDeduction]);

    const handlerChangeStatus = (id, status) => {

        if(status != 'PAID')
        {
            swal({
                title: "You want change the status to "+ status +"?",
                text: "Change status!",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
            .then((willDelete) => {

                if(willDelete)
                {
                    LoadingShow();

                    fetch(url_general +'payment-team/status-change/'+ id +'/'+ status)
                    .then(response => response.json())
                    .then(response => {

                        if(response.stateAction)
                        {
                            swal("PAYMENT TEAM status changed!", {

                                icon: "success",
                            });

                            setPaymentStatus(status);
                        }
                    });
                }
            });
        }
        else if(status == 'PAID')
        {
            swal({
                text: 'Enter transaction number',
                content: "input",
                button: {
                    text: "Save",
                    closeModal: false,
                },
            })
            .then(numberTransaction => {

                if(numberTransaction != null && numberTransaction != '')
                {
                    swal.close();

                    LoadingShowMap();

                    fetch(url_general +'payment-team/status-change/'+ id +'/'+ status +'?numberTransaction='+ numberTransaction)
                    .then(response => response.json())
                    .then(response => {

                        if(response.stateAction == true)
                        {
                            swal("PAYMENT TEAM status changed!", {

                                icon: "success",
                            });

                            setNumberTransaction(numberTransaction);
                            setPaymentStatus(status);
                        }
                        else
                        {
                            swal("There was an error, try again!", {

                                icon: "error",
                            });
                        }

                        LoadingHideMap();
                    });
                }
                else
                {
                    swal.close();

                    swal('Attention!', 'You have to enter the transaction number', 'warning');
                }
            });
        }
    }

    const handlerRecalculate = (id) => {

        swal({
            title: "You want RECALCULATE?",
            text: "Change status!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {

            if(willDelete)
            {
                LoadingShow();

                fetch(url_general +'payment-team/recalculate/'+ id)
                .then(response => response.json())
                .then(response => {

                    if(response.statusCode)
                    {
                        swal("PAYMENT TEAM was recalculated!", {

                            icon: "success",
                        });

                        listByRoute(idPaymentGeneral);
                        listByPODFailed(idPaymentGeneral);
                        listRevertShipments(idPaymentGeneral);
                        ListAdjustmentPayment(idPaymentGeneral);
                    }
                    else
                    {
                       swal("There was an error, try again!", {

                            icon: "success",
                        });
                    }
                });
            }
        });
    }

    return (
        <section className="section">
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row">
                                    <div className="col-lg-10 form-group text-primary">
                                        <h3>PAYMENT TEAM - { teamName }</h3>
                                        <h5>WEEK: { startDate +' - '+ endDate}</h5>
                                        <h5>PAYMENT: { paymentId }</h5>
                                        <h5 className="text-info">CONFIRMATION CODE: { numberTransaction }</h5>
                                    </div>
                                    <div className="col-lg-2 form-group text-info">
                                        <div className="row">
                                            <div className="col-lg-12 form-group">
                                                <h5>ACTIONS</h5>
                                            </div>
                                            <div className="col-lg-12  form-group">
                                                {
                                                    (
                                                        paymentStatus == 'TO APPROVE'
                                                        ?
                                                            <>
                                                                <button className="btn btn-primary font-weight-bold form-control text-center btn-sm mb-1" onClick={ () => handlerRecalculate(paymentId) }>
                                                                    RECALCULATE
                                                                </button>
                                                                <button className="btn btn-info font-weight-bold form-control text-center btn-sm" onClick={ () => handlerChangeStatus(paymentId, 'PAYABLE') }>
                                                                    { paymentStatus }
                                                                </button>
                                                            </>
                                                        : ''
                                                    )
                                                }
                                                {
                                                    (
                                                        paymentStatus == 'PAYABLE'
                                                        ?
                                                            <button className="btn btn-warning font-weight-bold form-control text-center btn-sm" onClick={ () => handlerChangeStatus(paymentId, 'PAID') }>
                                                                { paymentStatus }
                                                            </button>
                                                        : ''
                                                    )
                                                }
                                                {
                                                    (
                                                        paymentStatus == 'PAID'
                                                        ?
                                                            <span className="alert-success font-weight-bold form-control text-center" style={ {padding: '5px', fontWeight: 'bold', borderRadius: '.2rem'} }>
                                                                { paymentStatus }
                                                            </span>
                                                        : ''
                                                    )
                                                }
                                            </div>
                                            <div className="col-lg-6 form-group">
                                                <button className="btn btn-success btn-sm m-1" onClick={ () => handlerExportPayment(paymentId) } title="Download Detail">
                                                    <i className="ri-file-excel-fill"></i> Detail
                                                </button>
                                            </div>

                                            {
                                                (
                                                    paymentStatus == 'PAID'
                                                    ?
                                                        <div className="col-lg-6 form-group">
                                                            <button className="btn btn-warning btn-sm m-1 text-white" onClick={ () => handlerExportPaymentReceipt(paymentId) } title="Download Receipt">
                                                                <i className="ri-file-excel-fill"></i> Receipt
                                                            </button>
                                                        </div>
                                                    :
                                                        ''
                                                )
                                            }
                                        </div>
                                    </div>
                                    <hr/>
                                </div>

                                {
                                    (
                                        paymentStatus == 'TO APPROVE'
                                        ?
                                            <div className="row">
                                                <div className="col-lg-12 form-group text-info">
                                                    ADD ADJUSTMENT
                                                </div>
                                                <form onSubmit={ handlerSaveAdjustment }>
                                                    <div className="row">
                                                        <div className="col-lg-3 mb-3">
                                                            <label htmlFor="" className="form">AMOUNT</label>
                                                            <input type="number" value={ amount } onChange={ (e) => setAmount(e.target.value) } className="form-control" required/>
                                                        </div>
                                                        <div className="col-lg-9 mb-3">
                                                            <label htmlFor="" className="form">DESCRIPTION</label>
                                                            <input type="text" value={ description } onChange={ (e) => setDescription(e.target.value) } className="form-control" minLength="4" maxLength="500" required/>
                                                        </div>
                                                        <div className="col-lg-3 mb-3">
                                                            <button className="btn btn-primary btn-sm form-control">SAVE</button>
                                                        </div>
                                                    </div>
                                                </form>
                                                <hr/>
                                            </div>
                                        : ''
                                    )
                                }

                                <div className="row">
                                    <div className="col-lg-12 form-group text-info">
                                        RESUMEN
                                    </div>
                                </div>
                                <div className="row">
                                    <div className="col-lg-12 form-group text-primary">
                                        DELIVERY
                                    </div>
                                    <div className="col-lg-12">
                                        <table className="table table-hover table-condensed table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>ROUTE</th>
                                                    <th>TOTAL PIECES</th>
                                                    <th>AVERAGE COST</th>
                                                    <th>TOTAL ROUTE</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                { listPaymentDetailRoute }
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th>TOTAL DELIVERY</th>
                                                    <th className="text-right">{ totalPieces }</th>
                                                    <th className="text-right">$ { totalAverageCost }</th>
                                                    <th className="text-right">$ { totalRoute }</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    <hr/>
                                </div>
                                <div className="row">
                                    <div className="col-lg-12 form-group text-primary">
                                        REVERTED SHIPMENTS <i className="bi bi-patch-question-fill text-danger" data-tooltip-id="myTooltipReverted1"></i>
                                        <ReactTooltip
                                            id="myTooltipReverted1"
                                            place="top"
                                            variant="dark"
                                            content="Reverted shipments are packages
                                                    that were paid in error to the carrier and that
                                                    were marked for a discount on the next invoice.
                                                    The packages shown here are not discounts on the invoice,
                                                     it is only to control which packages within this invoice are not valid."
                                            style={ {width: '40%'} }
                                          />
                                    </div>
                                    <div className="col-lg-12">
                                        <table className="table table-hover table-condensed table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>PACKAGE-ID</th>
                                                    <th>REASON</th>
                                                    <th>PRICE</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                { listTableRevertShipments }
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th></th>
                                                    <th>TOTAL REVERTED</th>
                                                    <th className="text-right">$ { totalRevertShipment}</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    <hr/>
                                </div>
                                <div className="row">
                                    <div className="col-lg-12 form-group text-primary">
                                        INVALID POD&nbsp;
                                        <button className="btn btn-primary btn-sm" style={ {display: 'none'} }>
                                            <i className="bi bi-plus-circle"></i>
                                        </button>

                                        {
                                            (
                                                paymentStatus == 'TO APPROVE'
                                                ?
                                                    <>
                                                        <br/>
                                                            <form onSubmit={ handlerInserPDOFailed } autoComplete="off">
                                                                <div className="form-group">
                                                                    <label htmlFor="" className="text-success">PACKAGE ID</label>
                                                                    <input id="Reference_Number_1" type="text" className="form-control" value={ Reference_Number_1_POD_Failed } onChange={ (e) => setReference_Number_1_POD_Failed(e.target.value) } maxLength="24" required/>
                                                                </div>
                                                            </form>
                                                    </>
                                                : ''
                                            )
                                        }

                                    </div>
                                    <div className="col-lg-12">
                                        <table className="table table-hover table-condensed table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>PACKAGE-ID</th>
                                                    <th>PRICE</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                { listPaymentDetailPODFailed }
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th>TOTAL</th>
                                                    <th className="text-right">$ 0.000</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    <hr/>
                                </div>
                                <div className="row">
                                    <div className="col-lg-12 form-group text-primary">
                                        ADJUSTMENTS
                                    </div>
                                    <div className="col-lg-12">
                                        <table className="table table-hover table-condensed table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>DESCRIPTION</th>
                                                    <th>AMOUNT</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                { listTablePaymentAdjustment }
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th>TOTAL</th>
                                                    <th className="text-right">$ { totalAdjustment }</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    <hr/>
                                </div>
                                <div className="row">
                                    <div className="col-lg-12">
                                        <table className="table table-hover table-condensed table-bordered">
                                            <tfoot>
                                                <tr>
                                                    <th>TOTAL FACTURA</th>
                                                    <th><h6 className="text-primary text-right">$ { totalInvoice }</h6></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    <hr/>
                                </div>
                            </h5>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

export default PaymentAdjustment;

if (document.getElementById('paymentAdjustment'))
{
    ReactDOM.render(<PaymentAdjustment />, document.getElementById('paymentAdjustment'));
}
