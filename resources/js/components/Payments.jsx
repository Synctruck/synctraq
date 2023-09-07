import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'

function Payments() {

    const [listReport, setListReport]         = useState([]);
    const [listTeam, setListTeam]             = useState([]);
    const [listDriver, setListDriver]         = useState([]);
    const [roleUser, setRoleUser]             = useState([]);

    const [quantityPayment, setQuantityPayment] = useState(0);
    const [totalPaymentTeam, setTotalPaymentTeam] = useState(0);

    const [listRoute, setListRoute]  = useState([]);
    const [listState , setListState] = useState([]);

    const [dateInit, setDateInit] = useState(auxDateInit);
    const [dateEnd, setDateEnd]   = useState(auxDateInit);
    const [team, setTeam]         = useState('');
    const [idTeam, setIdTeam]     = useState(0);
    const [idDriver, setIdDriver] = useState(0);

    const [RouteSearch, setRouteSearch]   = useState('all');
    const [StateSearch, setStateSearch]   = useState('all');
    const [StatusSearch, setStatusSearch] = useState('all');

    const [page, setPage]                 = useState(1);
    const [totalPage, setTotalPage]       = useState(0);
    const [totalPackage, setTotalPackage] = useState(0);

    const [file, setFile]             = useState('');
    const [btnDisplay, setbtnDisplay] = useState('none');

    const [viewButtonSave, setViewButtonSave] = useState('none');

    const inputFileRef  = React.useRef();

    useEffect(() => {

        if(String(file) == 'undefined' || file == '')
        {
            setViewButtonSave('none');
        }
        else
        {
            setViewButtonSave('block');
        }

    }, [file]);

    useEffect( () => {

        listAllTeam();

    }, []);

    useEffect(() => {

        listReportDispatch(1, RouteSearch, StateSearch);

    }, [dateInit, dateEnd, idTeam, StatusSearch]);


    const listReportDispatch = (pageNumber, routeSearch, stateSearch) => {

        setListReport([]);

        fetch(url_general +'payment-team/list/'+ dateInit +'/'+ dateEnd +'/'+ idTeam +'/'+ StatusSearch +'?page='+ pageNumber)
        .then(res => res.json())
        .then((response) => {

            setListReport(response.paymentList.data);
            setTotalPackage(response.paymentList.total);
            setTotalPage(response.paymentList.per_page);
            setPage(response.paymentList.current_page);
            setQuantityPayment(response.paymentList.total);
            setTotalPaymentTeam(response.totalPayments);
        });
    }

    const listAllTeam = () => {

        fetch(url_general +'team/listall')
        .then(res => res.json())
        .then((response) => {

            setListTeam(response.listTeam);
        });
    }

    const handlerChangeDateInit = (date) => {

        setDateInit(date);
    }

    const handlerChangeDateEnd = (date) => {

        setDateEnd(date);
    }

    const handlerChangePage = (pageNumber) => {

        listReportDispatch(pageNumber, RouteSearch, StateSearch);
    }

    const handlerExportPayment = (id) => {

        location.href = url_general +'payment-team/export/'+ id;
    }

    const handlerExportPaymentReceipt = (id) => {

        location.href = url_general +'payment-team/export-receipt/'+ id;
    }

    const handlerChangeStatus = (id, status) => {

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

                        listReportDispatch(1, RouteSearch, StateSearch);
                    }
                });
            }
        });
    }

    const handlerChangeFormatPrice = (number) => {

        const exp = /(\d)(?=(\d{3})+(?!\d))/g;
        const rep = '$1,';
        let arr   = number.toString().split('.');
        arr[0]    = arr[0].replace(exp,rep);

        return arr[1] ? arr.join('.'): arr[0];
    }

    const listReportTable = listReport.map( (payment, i) => {

        let totalDelivery   = handlerChangeFormatPrice(payment.totalDelivery);
        let totalRevert     = handlerChangeFormatPrice(payment.totalRevert);
        let totalAdjustment = handlerChangeFormatPrice(payment.totalAdjustment);
        let total           = handlerChangeFormatPrice(payment.total);
        let averagePrice    = handlerChangeFormatPrice(payment.averagePrice);

        return (

            <tr key={i}>
                <td style={ { width: '100px'} }>
                    <b>{ payment.created_at.substring(5, 7) }-{ payment.created_at.substring(8, 10) }-{ payment.created_at.substring(0, 4) }</b><br/>
                    { payment.created_at.substring(11, 19) }
                </td>
                <td>
                    <b>{ payment.id }</b><br/>
                    <b className="text-primary">{ ( payment.team? payment.team.name : '') }</b>
                </td>
                <td>
                    <b className="text-warning">{ (payment.user_payable ? payment.user_payable.name : '' )}</b> <br/>
                    <b className="text-success">{ (payment.user_paid ? payment.user_paid.name : '' )}</b> <br/>
                </td>
                <td>{ payment.startDate.substring(5, 7) }-{ payment.startDate.substring(8, 10) }-{ payment.startDate.substring(0, 4) }</td>
                <td>{ payment.endDate.substring(5, 7) }-{ payment.endDate.substring(8, 10) }-{ payment.endDate.substring(0, 4) }</td>
                <td className="text-center">
                    <b>{ payment.totalPieces }</b>
                </td>
                <td className="text-primary text-right"><h5><b>{ totalDelivery }</b></h5></td>
                <td className="text-danger text-right"><h5><b>{ totalRevert }</b></h5></td>
                <td className="text-warning text-right"><h5><b>{ totalAdjustment }</b></h5></td>
                <td className="text-success text-right"><h5><b>{ total }</b></h5></td>
                <td className="text-info text-right"><h5><b>{ averagePrice }</b></h5></td>
                <td>
                    { 
                        (
                            payment.status == 'TO APPROVE'
                            ? 
                                <button className="btn btn-info font-weight-bold text-center btn-sm" onClick={ () => handlerChangeStatus(payment.id, 'PAYABLE') }>
                                    { payment.status }
                                </button>
                            : ''
                        )
                    }
                    {
                        (
                            payment.status == 'PAYABLE'
                            ? 
                                <button className="btn btn-warning font-weight-bold text-center btn-sm" onClick={ () => handlerChangeStatus(payment.id, 'PAID') }>
                                    { payment.status }
                                </button>
                            : ''
                        )
                    }
                    { 
                        (
                            payment.status == 'PAID'
                            ? 
                                <span className="alert-success font-weight-bold text-center" style={ {padding: '5px', fontWeight: 'bold', borderRadius: '.2rem'} }>
                                    { payment.status }
                                </span>
                            : ''
                        )
                    }
                </td>
                <td>
                    { 
                        (
                            payment.status == 'TO APPROVE'
                            ? 
                                <button className="btn btn-primary btn-sm m-1" onClick={ () => handlerOpenModalEditPayment(payment.id, payment.totalDelivery) } title="Export Payment">
                                    <i className="bx bx-edit-alt"></i>
                                </button> 
                            : ''
                        )
                    }
                    
                    <button className="btn btn-success btn-sm m-1" onClick={ () => handlerExportPayment(payment.id) } title="Export Payment">
                        <i className="ri-file-excel-fill"></i>
                    </button>
                    <button className="btn btn-warning btn-sm m-1 text-white" onClick={ () => handlerExportPaymentReceipt(payment.id) } title="Export Receipt">
                        <i className="ri-file-excel-fill"></i>
                    </button>
                </td>
            </tr>
        );
    });

    const [titleModal, setTitleModal]         = useState('');
    const [listAdjustment, setListAdjustment] = useState([]);

    const [totalAdjustment, setTotalAdjustment] = useState(0);
    const [totalDelivery, setTotalDelivery]     = useState('');
    const [idPayment, setidPayment]             = useState('');
    const [amount, setAmount]                   = useState('');
    const [description, setDescription]         = useState('');

    const handlerOpenModalEditPayment = (idPayment, totalDelivery) => {

        window.open(url_general +'payment-team/edit/'+ idPayment);

        /*setTotalDelivery(totalDelivery);
        setidPayment(idPayment);
        setTitleModal('PAYMENT TEAM - ADJUSTMENT');

        ListAdjustmentPayment(idPayment);

        let myModal = new bootstrap.Modal(document.getElementById('modalEditPayment'), {

            keyboard: true 
        });

        myModal.show();*/
    }

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

        setTotalAdjustment(total.toFixed(4));
    }

    const listPaymentAdjustmentModal = listAdjustment.map( (adjustment, i) => {

        return (

            <tr>
                <td>{ adjustment.description }</td>
                <td><h6 className={ (adjustment.amount >= 0 ? 'text-success text-right' : 'text-danger text-right') }>{ adjustment.amount } $</h6></td>
            </tr>
        );
    });

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

    const clearFormAdjustment = () => {

        setAmount('');
        setDescription('');
    }

    const modalEditPayment = <React.Fragment>
                                    <div className="modal fade" id="modalEditPayment" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog">
                                            <div className="modal-content">
                                                <div className="modal-header">
                                                    <h5 className="modal-title text-primary" id="exampleModalLabel">{ titleModal }</h5>
                                                    <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div className="modal-body">
                                                    <form onSubmit={ handlerSaveAdjustment }>
                                                        <div className="row">
                                                            <div className="col-lg-6 mb-3">
                                                                <label htmlFor="" className="form">PAYMENT: <span className="text-primary">{ idPayment }</span></label>
                                                            </div>
                                                            <div className="col-lg-6 mb-3">
                                                                <label htmlFor="" className="form">TOTAL DELIVERY: <span className="text-primary">{ totalDelivery } $</span></label>
                                                            </div>
                                                        </div>
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
                                                    <div className="row">
                                                        <div className="col-lg-12">
                                                            <table className="table table-hover table-condensed table-bordered">
                                                                <thead>
                                                                    <tr>
                                                                        <th>DESCRIPTION</th>
                                                                        <th>AMOUNT</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    { listPaymentAdjustmentModal }
                                                                </tbody>
                                                                <tfoot>
                                                                    <tr>
                                                                        <td><h6>TOTAL ADJUSTMENT</h6></td>
                                                                        <td className="text-right"><h6 className={ (totalAdjustment >= 0 ? 'text-success' : 'text-danger') }>{ totalAdjustment } $</h6></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><h6>TOTAL DELIVERY</h6></td>
                                                                        <td className="text-right"><h6 className='text-primary'>{ totalDelivery } $</h6></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><h6 className="text-success">TOTAL</h6></td>
                                                                        <td className="text-right"><h6 className='text-success'>{ (parseFloat(totalDelivery) + parseFloat(totalAdjustment)).toFixed(4) } $</h6></td>
                                                                    </tr>
                                                                </tfoot>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="modal-footer">
                                                    <button type="button" className="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </React.Fragment>;

    const listTeamSelect = listTeam.map( (team, i) => {

        return (

            <option value={ team.id } text={ team.name }>{ team.name }</option>
        );
    });

    const handlerExport = () => {

        location.href = 'payment-team/export-all/'+ dateInit +'/'+ dateEnd +'/'+ idTeam +'/'+ StatusSearch;
    }

    const handlerExportListAll = () => {

        listReport.forEach((payment, index) => {

            setTimeout(() => {

                handlerExportPayment(payment.id);
            }, index * 1500);
        });
    }

    return (

        <section className="section">
            { modalEditPayment }
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row">
                                    <div className="col-2 form-group">
                                        <button className="btn btn-success btn-sm form-control" onClick={  () => handlerExport() }>
                                            <i className="ri-file-excel-fill"></i> EXPORT
                                        </button>
                                    </div>
                                </div>
                                <div className="row">
                                    <div className="col-lg-2 mb-3">
                                        <label htmlFor="">Start date:</label>
                                        <input type="date" value={ dateInit } onChange={ (e) => handlerChangeDateInit(e.target.value) } className="form-control"/>
                                    </div>
                                    <div className="col-lg-2 mb-3">
                                        <label htmlFor="">End date:</label>
                                        <input type="date" value={ dateEnd } onChange={ (e) => handlerChangeDateEnd(e.target.value) } className="form-control"/>
                                    </div>
                                    <div className="col-lg-2 mb-3">
                                        <div className="form-group">
                                            <label htmlFor="">Team</label>
                                            <select name="" id="" className="form-control" onChange={ (e) => setIdTeam(e.target.value) } required>
                                               <option value="0">All</option>
                                                { listTeamSelect }
                                            </select>
                                        </div>
                                    </div>
                                    <div className="col-lg-2">
                                        <div className="row">
                                            <div className="col-lg-12">
                                                STATUS:
                                            </div>
                                            <div className="col-lg-12">
                                                <select name="" id="" className="form-control" onChange={ (e) => setStatusSearch(e.target.value) }>
                                                    <option value="all">All</option>
                                                    <option value="TO APPROVE">TO APPROVE</option>
                                                    <option value="PAYABLE">PAYABLE</option>
                                                    <option value="PAID">PAID</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div className="row">
                                    <div className="col-lg-4 mb-3">
                                        <b className="alert-success" style={ {borderRadius: '10px', padding: '10px', fontSize: '14px'} }>Payment Quantity: { quantityPayment }</b>
                                    </div>
                                    <div className="col-lg-4 mb-3">
                                        <b className="alert-success" style={ {borderRadius: '10px', padding: '10px', fontSize: '14px'} }>Total Payment Team: { totalPaymentTeam +' $' }</b>
                                    </div>
                                </div>
                            </h5>
                            <div className="row form-group table-responsive">
                                <div className="col-lg-12">
                                    <table className="table table-hover table-condensed table-bordered">
                                        <thead>
                                            <tr>
                                                <th><b>DATE</b></th>
                                                <th>
                                                    <b>N° PAYMENT</b><br/>
                                                    <b>TEAM</b>
                                                </th>
                                                <th>
                                                    <b>USERS</b><br/>
                                                </th>
                                                <th><b>START DATE</b></th>
                                                <th><b>END DATE</b></th>
                                                <th><b>PIECES</b></th>
                                                <th><b>TOTAL DELIVERY</b></th>
                                                <th><b>TOTAL REVERT</b></th>
                                                <th><b>TOTAL ADJUSTMENT</b></th>
                                                <th><b>TOTAL</b></th>
                                                <th><b>AVERAGE PRICE</b></th>
                                                <th><b>STATUS</b></th>
                                                <th>
                                                    <b>ACTION</b>&nbsp;
                                                    <button className="btn btn-success btn-sm" onClick={  () => handlerExportListAll() }>
                                                        <i className="ri-file-excel-fill"></i>
                                                    </button>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            { listReportTable }
                                        </tbody>
                                    </table>
                                </div>
                                <div className="col-lg-12">
                                    <Pagination
                                        activePage={page}
                                        totalItemsCount={totalPackage}
                                        itemsCountPerPage={totalPage}
                                        onChange={(pageNumber) => handlerChangePage(pageNumber)}
                                        itemClass="page-item"
                                        linkClass="page-link"
                                        firstPageText="First"
                                        lastPageText="Last"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

export default Payments;

if (document.getElementById('payments'))
{
    ReactDOM.render(<Payments />, document.getElementById('payments'));
}