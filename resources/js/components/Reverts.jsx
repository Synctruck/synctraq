import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'

function Reverts() {

    const [listToReverse, setListToReverse]         = useState([]);
    const [listTeam, setListTeam]             = useState([]);
    const [listDriver, setListDriver]         = useState([]);
    const [roleUser, setRoleUser]             = useState([]);

    const [quantityRevert, setQuantityRevert] = useState(0);
    const [totalPaymentRevert, setTotalPaymentRevert] = useState(0);

    const [listRoute, setListRoute]  = useState([]);
    const [listState , setListState] = useState([]);

    const [dateInit, setDateInit] = useState(auxDateInit);
    const [dateEnd, setDateEnd]   = useState(auxDateInit);
    const [team, setTeam]         = useState('');
    const [idTeam, setIdTeam]     = useState(0);
    const [idDriver, setIdDriver] = useState(0);

    const [Reference_Number_1, setReference_Number_1] = useState('');
    const [Reason, setReason] = useState('');
    const [disabledButton, setDisabledButton]         = useState(false);

    const [RouteSearch, setRouteSearch]   = useState('all');
    const [StateSearch, setStateSearch]   = useState('all');
    const [Status, setStatus]             = useState('all');

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

        listToReverseDispatch(1, RouteSearch, Status);

    }, [dateInit, dateEnd, idTeam, Status]);


    const listToReverseDispatch = (pageNumber, routeSearch, status) => {

        setListToReverse([]);

        fetch(url_general +'payment-revert/'+ dateInit +'/'+ dateEnd +'/'+ idTeam +'/'+ status +'?page='+ pageNumber)
        .then(res => res.json())
        .then((response) => {

            setListToReverse(response.toReversePackagesList.data);
            setTotalPackage(response.toReversePackagesList.total);
            setTotalPage(response.toReversePackagesList.per_page);
            setPage(response.toReversePackagesList.current_page);
            setQuantityRevert(response.toReversePackagesList.total);
            setTotalPaymentRevert(response.totalReverts);
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

        listToReverseDispatch(pageNumber, RouteSearch, StateSearch);
    }

    const handlerExportPayment = (id) => {

        location.href = url_general +'payment-team/export/'+ id;
    }

    const handlerChangeFormatPrice = (number) => {

        const exp = /(\d)(?=(\d{3})+(?!\d))/g; 
        const rep = '$1,';
        let arr   = number.toString().split('.'); 
        arr[0]    = arr[0].replace(exp,rep);

        return arr[1] ? arr.join('.'): arr[0];
    }

    const listToReverseTable = listToReverse.map( (paymentRevert, i) => {

        let total = handlerChangeFormatPrice(paymentRevert.priceToRevert);

        return (

            <tr key={i}>
                <td style={ { width: '100px'} }>
                    <b>{ paymentRevert.created_at.substring(5, 7) }-{ paymentRevert.created_at.substring(8, 10) }-{ paymentRevert.created_at.substring(0, 4) }</b><br/>
                    { paymentRevert.created_at.substring(11, 19) }
                </td>
                <td><b>{ paymentRevert.shipmentId }</b></td>
                <td><b>{ paymentRevert.idPaymentTeam }</b></td>
                <td><b>{ paymentRevert.team.name }</b></td>
                <td className="text-danger text-right">
                    <h5><b>{ total +' $' }</b></h5>
                </td>
                <td style={ {display: 'none'} }>
                    <button className="btn btn-primary form-control" onClick={ () => handlerExportPayment(paymentRevert.shipmentId) }>
                        <i className="ri-file-excel-fill"></i> EXPORT DETAIL
                    </button>
                </td>
            </tr>
        );
    });

    const handlerOpenModalInsertToReverts = (PACKAGE_ID) => {

        let myModal = new bootstrap.Modal(document.getElementById('modalInsertToReverts'), {

            keyboard: false,
            backdrop: 'static',
        });

        myModal.show();

        setReference_Number_1('');
        setReason('');
    }

    const handlerInsertToReverts = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('Reference_Number_1', Reference_Number_1);
        formData.append('Reason', Reason);

        LoadingShowMap();

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch(url_general +'payment-revert/insert', {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json())
        .then((response) => {

                if(response.statusCode === true)
                {
                    swal("The package was registered as Revert!", {

                        icon: "success",
                    });

                    setReference_Number_1('');
                    setReason('');

                    listToReverseDispatch(1, RouteSearch, StateSearch);
                }
                else if(response.statusCode == 'notExists')
                {
                    swal("The package was not INVOICED or was REVERTED!", {

                        icon: "warning",
                    });
                }
                else if(response.statusCode == 'notInPaid')
                {
                    swal("The INVOICE of the package entered, it's not is in status PAID.", {

                        icon: "warning",
                    });
                }
                else if(response.statusCode == 'error')
                {
                    swal("There was a problem performing the process, please try again!", {

                        icon: "warning",
                    });
                }

                LoadingHideMap();
            },
        );
    }

    const modalInsertToReverts = <React.Fragment>
                                    <div className="modal fade" id="modalInsertToReverts" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog modal-md">
                                            <form onSubmit={ handlerInsertToReverts }>
                                                <div className="modal-content">
                                                    <div className="modal-header">
                                                        <h5 className="modal-title text-primary" id="exampleModalLabel">Register To Reverse Packages</h5>
                                                        <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div className="modal-body">
                                                        <div className="row">
                                                            <div className="col-lg-12 mb-2">
                                                                <div className="form-group">
                                                                    <label className="form">PACKAGE ID</label>
                                                                    <div id="divReference_Number_1" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" id="Reference_Number_1" value={ Reference_Number_1 } className="form-control" onChange={ (e) => setReference_Number_1(e.target.value) } minLength="4" maxLength="25" required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-12 mb-2">
                                                                <div className="form-group">
                                                                    <label className="form">REASON</label>
                                                                    <div id="divReference_Number_1" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" id="Reason" value={ Reason } className="form-control" onChange={ (e) => setReason(e.target.value) } minLength="5" maxLength="150" required/>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div className="modal-footer">
                                                        <button type="button" className="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                        <button className="btn btn-primary" disabled={ disabledButton }>Register</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </React.Fragment>;

    const listTeamSelect = listTeam.map( (team, i) => {

        return (

            <option value={ team.id } text={ team.name }>{ team.name }</option>
        );
    });

    const listDriverSelect = listDriver.map( (driver, i) => {

        return (

            <option value={ driver.id }>{ driver.name +' '+ driver.nameOfOwner }</option>
        );
    });

    const handlerChangeRoute = (routes) => {

        if(routes.length != 0)
        {
            let routesSearch = '';

            routes.map( (route) => {

                routesSearch = routesSearch == '' ? route.value : routesSearch +','+ route.value;
            });

            setRouteSearch(routesSearch);

            listToReverseDispatch(1, routesSearch, StateSearch);
        }
        else
        {
            setRouteSearch('all');

            listToReverseDispatch(1, 'all', StateSearch);
        }
    };

    const [optionsRoleSearch, setOptionsRoleSearch] = useState([]);

    const listOptionRoute = (listRoutes) => {

        setOptionsRoleSearch([]);

        listRoutes.map( (route, i) => {

            optionsRoleSearch.push({ value: route.name, label: route.name });

            setOptionsRoleSearch(optionsRoleSearch);
        });
    }

    const handlerChangeState = (states) => {

        if(states.length != 0)
        {
            let statesSearch = '';

            states.map( (state) => {

                statesSearch = statesSearch == '' ? state.value : statesSearch +','+ state.value;
            });

            setStateSearch(statesSearch);

            listToReverseDispatch(page, RouteSearch, statesSearch);
        }
        else
        {
            setStateSearch('all');

            listToReverseDispatch(page, RouteSearch, 'all');
        }
    };

    const [optionsStateSearch, setOptionsStateSearch] = useState([]);

    const listOptionState = (listState) => {

        setOptionsStateSearch([]);

        listState.map( (state, i) => {

            optionsStateSearch.push({ value: state.Dropoff_Province, label: state.Dropoff_Province });

            setOptionsStateSearch(optionsStateSearch);
        });
    }

    const handlerRegisterPayment = () => {

        if(idTeam != 0)
        {
            swal({
                title: "You want to register the payment of the TEAM: "+ team +" ?",
                text: "Start Date: "+ dateInit +' | End Date: '+ dateEnd,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
            .then((confirmation) => {

                if(confirmation)
                {
                    const formData = new FormData();

                    formData.append('idTeam', idTeam);
                    formData.append('startDate', dateInit);
                    formData.append('endDate', dateEnd);

                    let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    LoadingShow(); 

                    fetch(url_general +'payment-delivery/insert', {
                        headers: { "X-CSRF-TOKEN": token },
                        method: 'post',
                        body: formData
                    })
                    .then(res => res.json()).
                    then((response) => {

                            if(response.stateAction == 'incorrectDate')
                            {
                                swal("Select a correct date range!", {

                                    icon: "error",
                                });
                            }
                            else if(response.stateAction == 'paymentExists')
                            {
                                swal("There is already a payment for the selected filters!", {

                                    icon: "warning",
                                });
                            }
                            else if(response.stateAction)
                            {
                                swal("Payment was made correctly!", {

                                    icon: "success",
                                });

                                document.getElementById('fileImport').value = '';

                                listAllPackage();
                                setbtnDisplay('none');
                            }

                            LoadingHide();
                        },
                    );
                }
            });
        }
        else
        {
            swal("You must select a TEAM for checkout registration!", {

                icon: "warning",
            });
        }
    }

    return (

        <section className="section">
            { modalInsertToReverts }
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row">
                                    <div className="col-lg-2 mb-3">
                                        <button className="btn btn-info btn-sm form-control text-white" onClick={ () => handlerOpenModalInsertToReverts() }>REGISTER TO REVERT</button>
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
                                    <div className="col-lg-2" style={ {display: 'none'} }>
                                        <div className="row">
                                            <div className="col-lg-12">
                                                STATUS:
                                            </div>
                                            <div className="col-lg-12">
                                                <select name="" id="" className="form-control" onChange={ (e) => setStatusSearch(e.target.value) }>
                                                    <option value="all">All</option>
                                                    <option value="Payable">PAYABLE</option>
                                                    <option value="Paid">PAID</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div className="row">
                                    <div className="col-lg-4 mb-3">
                                        <b className="alert-success" style={ {borderRadius: '10px', padding: '10px', fontSize: '14px'} }>Revert Quantity: { quantityRevert }</b>
                                    </div>
                                    <div className="col-lg-4 mb-3">
                                        <b className="alert-danger" style={ {borderRadius: '10px', padding: '10px', fontSize: '14px'} }>Total Payment Team: { totalPaymentRevert +' $' }</b>
                                    </div>
                                </div>
                            </h5>
                            <div className="row form-group table-responsive">
                                <div className="col-lg-12">
                                    <table className="table table-hover table-condensed table-bordered">
                                        <thead>
                                            <tr>
                                                <th><b>DATE</b></th>
                                                <th><b>PACKAGE ID</b></th>
                                                <th><b>PAYMENT N°</b></th>
                                                <th><b>TEAM</b></th>
                                                <th><b>TOTAL</b></th>
                                                <th style={ {display: 'none'} }><b>ACTION</b></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            { listToReverseTable }
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

export default Reverts;

if (document.getElementById('reverts'))
{
    ReactDOM.render(<Reverts />, document.getElementById('reverts'));
}
