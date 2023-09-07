import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'

function PaymentAdjustment() {

    const [teamName, setTeamName]   = useState(teamNameGeneral);
    const [paymentId, setPaymentId] = useState(paymentIdGeneral);
    const [paymentStatus, setPaymentStatus] = useState(paymentStatusGeneral); 
    const [startDate, setStartDate] = useState(startDateGeneral);
    const [endDate, setEndDate]     = useState(endDateGeneral);

    const [paymentTeamDetailRouteList, setPaymentTeamDetailRouteList] = useState([]);
    const [totalPieces, setTotalPieces] = useState(0);
    const [totalAverageCost, setTotalAverageCost] = useState(0);
    const [totalRoute, setTotalRoute] = useState(0);

    const [paymentTeamDetailPODFailedList, setPaymentTeamDetailPODFailedList] = useState([]);
    const [Reference_Number_1_POD_Failed, setReference_Number_1_POD_Failed] = useState('');

    const [totalAdjustment, setTotalAdjustment] = useState(0);
    const [totalDelivery, setTotalDelivery]     = useState('');
    const [idPayment, setidPayment]             = useState('');
    const [amount, setAmount]                   = useState('');
    const [description, setDescription]         = useState('');

    useEffect(() => {

        listByRoute(idPaymentGeneral);
        listByPODFailed(idPaymentGeneral);
    }, []);

    const listByRoute = (idPayment) => {

        fetch(url_general +'payment-team/list-by-route/'+ idPayment)
        .then(res => res.json())
        .then((response) => {
            
            setPaymentTeamDetailRouteList(response.paymentTeamDetailRouteList);
            setTotalDelivery(response.totalDelivery);

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
        setTotalRoute(auxTotalRoute.toFixed(2));
        setTotalAverageCost(auxTotalAverageCost.toFixed(2));
    }

    const handlerSaveAdjustment = (e) => {

        LoadingShowMap();

        e.preventDefault();

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const formData = new FormData();

        formData.append('idPaymentTeam', idPayment);
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
                    ListAdjustmentPayment(idPayment);
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

    const ListAdjustmentPayment = (idPayment) => {

        LoadingShowMap();

        fetch(url_general +'payment-team-adjustment/'+ idPayment)
        .then(response => response.json())
        .then(response => {

            LoadingHideMap();

            //setListAdjustment(response.listAdjustment);

            handlerCalculateTotalAdjustment(response.listAdjustment);
        });
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
                <td className="text-right">$ { paymentDetail.totalRoute / paymentDetail.totalPieces }</td>
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
                <td className="text-right">$ 0.00</td>
            </tr>
        );
    });

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
                                    </div>
                                    <div className="col-lg-2 form-group text-info">
                                        <div className="row">
                                            <div className="col-lg-12 form-group">
                                                <h5>ACTIONS</h5>
                                            </div>
                                            <div className="col-lg-12  form-group">
                                                <button className="btn btn-info btn-sm form-control">{ paymentStatus }</button>
                                            </div>
                                            <div className="col-lg-4 form-group">
                                                <button className="btn btn-success btn-sm m-1" onClick={ () => handlerExportPayment(paymentId) } title="Download Detail">
                                                    <i className="ri-file-excel-fill"></i>
                                                </button>
                                            </div>
                                            <div className="col-lg-4 form-group">
                                                <button className="btn btn-warning btn-sm m-1 text-white" onClick={ () => handlerExportPaymentReceipt(paymentId) } title="Download Receipt">
                                                    <i className="ri-file-excel-fill"></i>
                                                </button>
                                            </div>
                                            <div className="col-lg-4 form-group">
                                                <button className="btn btn-warning btn-sm m-1 text-white" onClick={ () => handlerExportPaymentReceipt(paymentId) } title="Recalculate">
                                                    <i className="ri-file-excel-fill"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <hr/>
                                </div>
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
                                                    <th>{ totalPieces }</th>
                                                    <th>$ { totalAverageCost }</th>
                                                    <th>$ { totalRoute }</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    <hr/>
                                </div>
                                <div className="row">
                                    <div className="col-lg-12 form-group text-primary">
                                        REVERTED SHIPMENTS <i className="bi bi-patch-question-fill text-danger" title=""></i>
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
                                                
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th></th>
                                                    <th>TOTAL REVERTED</th>
                                                    <th></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    <hr/>
                                </div>
                                <div className="row">
                                    <div className="col-lg-12 form-group text-primary">
                                        INVALID POD&nbsp;
                                        <button className="btn btn-primary btn-sm">
                                            <i className="bi bi-plus-circle"></i>
                                        </button>
                                        <br/>
                                        <form onSubmit={ handlerInserPDOFailed } autoComplete="off">
                                            <div className="form-group">
                                                <label htmlFor="" className="text-success">PACKAGE ID</label>
                                                <input id="Reference_Number_1" type="text" className="form-control" value={ Reference_Number_1_POD_Failed } onChange={ (e) => setReference_Number_1_POD_Failed(e.target.value) } maxLength="24" required/>
                                            </div>
                                        </form>
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
                                                    <th></th>
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
                                                
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th>TOTAL</th>
                                                    <th></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    <hr/>
                                </div>
                                <div className="row">
                                    <div className="col-lg-12">
                                        <table className="table table-hover table-condensed table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>TOTAL FACTURA</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
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