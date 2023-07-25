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

    const handlerConfirmInvoiced = (id, status) => {

        if(status == 'Payable')
        {
            swal({
                title: "You want change the status PAYABLE to PAID?",
                text: "Change status!",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
            .then((willDelete) => {

                if(willDelete)
                {
                    LoadingShow();

                    fetch(url_general +'payment-team/confirm/'+ id)
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
    }

    const handlerChangeFormatPrice = (number) => {

        const exp = /(\d)(?=(\d{3})+(?!\d))/g;
        const rep = '$1,';
        let arr   = number.toString().split('.');
        arr[0]    = arr[0].replace(exp,rep);

        return arr[1] ? arr.join('.'): arr[0];
    }

    const listReportTable = listReport.map( (payment, i) => {

        let total = handlerChangeFormatPrice(payment.total);

        return (

            <tr key={i}>
                <td style={ { width: '100px'} }>
                    <b>{ payment.created_at.substring(5, 7) }-{ payment.created_at.substring(8, 10) }-{ payment.created_at.substring(0, 4) }</b><br/>
                    { payment.created_at.substring(11, 19) }
                </td>
                <td><b>{ payment.id }</b></td>
                <td><b>{ payment.team.name }</b></td>
                <td>{ payment.startDate.substring(5, 7) }-{ payment.startDate.substring(8, 10) }-{ payment.startDate.substring(0, 4) }</td>
                <td>{ payment.endDate.substring(5, 7) }-{ payment.endDate.substring(8, 10) }-{ payment.endDate.substring(0, 4) }</td> 
                <td className="text-success text-right"><h5><b>{ '$ '+ total }</b></h5></td>
                <td>
                    <button className={ (payment.status == 'Payable' ? 'btn btn-danger font-weight-bold text-center' : 'btn btn-success font-weight-bold')} onClick={ () => handlerConfirmInvoiced(payment.id, payment.status) }>
                        { payment.status }
                    </button>
                </td>
                <td>
                    <button className="btn btn-primary form-control" onClick={ () => handlerExportPayment(payment.id) }>
                        <i className="ri-file-excel-fill"></i> EXPORT DETAIL
                    </button>
                </td>
            </tr>
        );
    });

    const [listViewImages, setListViewImages] = useState([]);

    const viewImages = (urlImage) => {

        setListViewImages(urlImage.split('https'));

        let myModal = new bootstrap.Modal(document.getElementById('modalViewImages'), {

            keyboard: true
        });

        myModal.show();
    }

    const listViewImagesModal = listViewImages.map( (image, i) => {

        if(i > 0)
        {
            return (

                <img src={ 'https'+ image } className="img-fluid mt-2"/>
            );
        }
    });

    const modalViewImages = <React.Fragment>
                                    <div className="modal fade" id="modalViewImages" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog">
                                            <div className="modal-content">
                                                <div className="modal-header">
                                                    <h5 className="modal-title text-primary" id="exampleModalLabel">View Images</h5>
                                                    <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div className="modal-body">
                                                    { listViewImagesModal }
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

            listReportDispatch(1, routesSearch, StateSearch);
        }
        else
        {
            setRouteSearch('all');

            listReportDispatch(1, 'all', StateSearch);
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

            listReportDispatch(page, RouteSearch, statesSearch);
        }
        else
        {
            setStateSearch('all');

            listReportDispatch(page, RouteSearch, 'all');
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
            { modalViewImages }
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
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
                                                    <option value="Payable">PAYABLE</option>
                                                    <option value="Paid">PAID</option>
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
                                                <th><b>N° PAYMENT</b></th>
                                                <th><b>TEAM</b></th>
                                                <th><b>START DATE</b></th>
                                                <th><b>END DATE</b></th>
                                                <th><b>TOTAL</b></th>
                                                <th><b>STATUS</b></th>
                                                <th><b>ACTION</b></th>
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
