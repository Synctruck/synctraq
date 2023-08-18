import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'

function Charge() {

    const [listReport, setListReport]         = useState([]);
    const [listDeliveries, setListDeliveries] = useState([]);
    const [listTeam, setListTeam]             = useState([]);
    const [listDriver, setListDriver]         = useState([]);
    const [listCompany , setListCompany]      = useState([]);
    const [roleUser, setRoleUser]             = useState([]);

    const [quantityDelivery, setQuantityDelivery]     = useState(0);
    const [totalChargeCompany, setTotalChargeCompany] = useState(0);

    const [listRoute, setListRoute]  = useState([]);
    const [listState , setListState] = useState([]);

    const [dateInit, setDateInit]             = useState(auxDateInit);
    const [dateEnd, setDateEnd]               = useState(auxDateInit);
    const [fuelPrice, setFuelPrice]           = useState('');
    const [fuelPercentage, setFuelPercentage] = useState('');
    const [idTeam, setIdTeam]                 = useState(0);
    const [idDriver, setIdDriver]             = useState(0);
    const [idCompany, setCompany]             = useState(0);

    const [RouteSearch, setRouteSearch]   = useState('all');
    const [StateSearch, setStateSearch]   = useState('all');
    const [StatusSearch, setStatusSearch] = useState('all');

    const [page, setPage]                 = useState(1);
    const [totalPage, setTotalPage]       = useState(0);
    const [totalPackage, setTotalPackage] = useState(0);

    useEffect( () => {

        listAllCompany();
        listAllTeam();
        listAllRoute();

    }, []);

    useEffect(() => {

        listReportDispatch(1, RouteSearch, StateSearch);

    }, [idCompany, dateInit, dateEnd, idTeam, idDriver, StatusSearch]);


    const listReportDispatch = (pageNumber, routeSearch, stateSearch) => {

        setListReport([]);

        fetch(url_general +'charge-company/list/'+ dateInit +'/'+ dateEnd +'/'+ idCompany +'/'+ StatusSearch  +'?page='+ pageNumber)
        .then(res => res.json())
        .then((response) => {

            setListReport(response.chargeList.data);
            setListDeliveries(response.listDeliveries);
            setTotalPackage(response.chargeList.total);
            setTotalPage(response.chargeList.per_page);
            setPage(response.chargeList.current_page);
            setQuantityDelivery(response.chargeList.total);

            setRoleUser(response.roleUser);
            setListState(response.listState);

            setTotalChargeCompany(response.totalCharge);

            if(listState.length == 0)
            {
                listOptionState(response.listState);
            }

            if(response.roleUser == 'Team')
            {
                listAllDriverByTeam(idUserGeneral);
                setIdTeam(idUserGeneral);
            }
        });
    }

    const listAllCompany = () => {

        setListCompany([]);

        fetch(url_general +'company/getAll')
        .then(res => res.json())
        .then((response) => {

            let CustomListCompany = [{id:0,name:"All companies"},...response.companyList];
            setCompany(0);
            setListCompany(CustomListCompany);

        });
    }

    const listAllTeam = () => {

        fetch(url_general +'team/listall')
        .then(res => res.json())
        .then((response) => {

            setListTeam(response.listTeam);
        });
    }

    const listAllDriverByTeam = (idTeam) => {

        setListDriver([]);
        setIdTeam(idTeam);
        setIdDriver(0);

        fetch(url_general +'driver/team/list/'+ idTeam)
        .then(res => res.json())
        .then((response) => {

            setListDriver(response.listDriver);
            setTeam(response.listDriver[0].nameTeam);
        });
    }

    const listAllRoute = () => {

        setListRoute([]);

        fetch(url_general +'routes/getAll')
        .then(res => res.json())
        .then((response) => {

            setListRoute(response.routeList);
            listOptionRoute(response.routeList);
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

    const handlerExportCharge = (id) => { 

        location.href = url_general +'charge-company/export/'+ id;
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

                fetch(url_general +'charge-company/confirm/'+ id +'/'+ status)
                .then(response => response.json())
                .then(response => {

                    if(response.stateAction)
                    {
                        swal("CHARGE COMPANY status changed!", {

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

    const listReportTable = listReport.map( (charge, i) => {

        let totalDelivery = handlerChangeFormatPrice(charge.totalDelivery);
        let totalRevert   = handlerChangeFormatPrice(charge.totalRevert);
        let total         = handlerChangeFormatPrice(charge.total);

        return (

            <tr key={i}>
                <td style={ { width: '100px'} }>
                    <b>{ charge.created_at.substring(5, 7) }-{ charge.created_at.substring(8, 10) }-{ charge.created_at.substring(0, 4) }</b><br/>
                    { charge.created_at.substring(11, 19) }
                </td>
                <td><b>{ charge.id }</b></td>
                <td><b>{ charge.company.name }</b></td>
                <td>{ charge.startDate.substring(5, 7) }-{ charge.startDate.substring(8, 10) }-{ charge.startDate.substring(0, 4) }</td>
                <td>{ charge.endDate.substring(5, 7) }-{ charge.endDate.substring(8, 10) }-{ charge.endDate.substring(0, 4) }</td>
                <td className="text-primary text-right"><h5><b>{ '$ '+ totalDelivery }</b></h5></td>
                <td className="text-danger text-right"><h5><b>{ '$ '+ totalRevert }</b></h5></td>
                <td className="text-success text-right"><h5><b>{ '$ '+ total }</b></h5></td>
                <td>
                    { 
                        (
                            charge.status == 'TO APPROVE'
                            ? 
                                <button className="btn btn-info font-weight-bold text-center btn-sm" onClick={ () => handlerChangeStatus(charge.id, 'APPROVED') }>
                                    { charge.status }
                                </button>
                            : ''
                        )
                    }
                    {
                        (
                            charge.status == 'APPROVED'
                            ? 
                                <button className="btn btn-warning font-weight-bold text-center btn-sm" onClick={ () => handlerChangeStatus(charge.id, 'PAID') }>
                                    { charge.status }
                                </button>
                            : ''
                        )
                    }
                    { 
                        (
                            charge.status == 'PAID'
                            ? 
                                <span className="alert-success font-weight-bold text-center" style={ {padding: '5px', fontWeight: 'bold', borderRadius: '.2rem'} }>
                                    { charge.status }
                                </span>
                            : ''
                        )
                    }
                </td>
                <td>
                    { 
                        (
                            charge.status == 'TO APPROVE'
                            ? 
                                <button className="btn btn-primary btn-sm m-1" onClick={ () => handlerOpenModalEditCharge(charge.id, charge.totalDelivery) } title="Export Payment">
                                    <i className="bx bx-edit-alt"></i>
                                </button>  
                            : ''
                        )
                    }

                    <button className="btn btn-success btn-sm m-1" onClick={ () => handlerExportCharge(charge.id) }>
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
    const [idCharge, setidCharge]               = useState('');
    const [amount, setAmount]                   = useState('');
    const [description, setDescription]         = useState('');

    const handlerOpenModalEditCharge = (idCharge, totalDelivery) => {

        setTotalDelivery(totalDelivery);
        setidCharge(idCharge);
        setTitleModal('CHARGE COMPANY - ADJUSTMENT');

        ListAdjustmentCharge(idCharge);

        let myModal = new bootstrap.Modal(document.getElementById('modalEditPayment'), {

            keyboard: true 
        });

        myModal.show();
    }

    const ListAdjustmentCharge = (idCharge) => {

        LoadingShowMap();

        fetch(url_general +'charge-company-adjustment/'+ idCharge)
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

        formData.append('idCharge', idCharge);
        formData.append('amount', amount);
        formData.append('description', description);

        fetch(url_general +'charge-company-adjustment/insert', {
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
                    ListAdjustmentCharge(idCharge);
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
                                                                <label htmlFor="" className="form">CHARGE: <span className="text-primary">{ idCharge }</span></label>
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

    const optionCompany = listCompany.map( (company, i) => {

        return <option value={ company.id }>{company.name}</option>
    })

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

    const handlerExport = () => {

        location.href= 'charge-company/export-all/'+ dateInit +'/'+ dateEnd +'/'+ idCompany +'/'+ StatusSearch;
    }

    const handlerExportListAll = () => {

        listReport.forEach((charge, index) => {

            setTimeout(() => {

                handlerExportCharge(charge.id);
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
                                    <dvi className="col-lg-2 mb-3"> 
                                        <div className="row">
                                            <div className="col-lg-12">
                                                Company:
                                            </div>
                                            <div className="col-lg-12">
                                                <select name="" id="" className="form-control" onChange={ (e) => setCompany(e.target.value) }>
                                                    <option value="" style={ {display: 'none'} }>Select...</option>
                                                    { optionCompany }
                                                </select>
                                            </div>
                                        </div>
                                    </dvi>
                                    {
                                        roleUser == 'Administrador'
                                        ?
                                            <>
                                                <div className="col-lg-2" style={ {display: 'none'} }>
                                                    <div className="form-group">
                                                        <label htmlFor="">Team</label>
                                                        <select name="" id="" className="form-control" onChange={ (e) => listAllDriverByTeam(e.target.value) } required>
                                                           <option value="0">All</option>
                                                            { listTeamSelect }
                                                        </select>
                                                    </div>
                                                </div>
                                                <div className="col-lg-2" style={ {display: 'none'} }>
                                                    <div className="form-group">
                                                        <label htmlFor="">Driver</label>
                                                        <select name="" id="" className="form-control" onChange={ (e) => setIdDriver(e.target.value) } required>
                                                           <option value="0">All</option>
                                                            { listDriverSelect }
                                                        </select>
                                                    </div>
                                                </div>
                                            </>
                                        :
                                            ''
                                    }

                                    {
                                        roleUser == 'Team'
                                        ?
                                            <>
                                                <div className="col-lg-3" style={ {display: 'none'} }>
                                                    <div className="form-group">
                                                        <label htmlFor="">Driver</label>
                                                        <select name="" id="" className="form-control" onChange={ (e) => setIdDriver(e.target.value) } required>
                                                           <option value="0">All</option>
                                                            { listDriverSelect }
                                                        </select>
                                                    </div>
                                                </div>
                                            </>
                                        :
                                            ''
                                    }

                                    <div className="col-lg-2 mb-3" style={ {display: 'none'} }>
                                        <div className="row">
                                            <div className="col-lg-12">
                                                State :
                                            </div>
                                            <div className="col-lg-12">
                                                <Select isMulti onChange={ (e) => handlerChangeState(e) } options={ optionsStateSearch } />
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-lg-2 mb-3" style={ {display: 'none'} }>
                                        <div className="row">
                                            <div className="col-lg-12">
                                                Route :
                                            </div>
                                            <div className="col-lg-12">
                                                <Select isMulti onChange={ (e) => handlerChangeRoute(e) } options={ optionsRoleSearch } />
                                            </div>
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
                                                    <option value="APPROVED">APPROVED</option>
                                                    <option value="PAID">PAID</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div className="row">
                                    <div className="col-lg-2 mb-3">
                                        <b className="alert-success" style={ {borderRadius: '10px', padding: '10px', fontSize: '14px'} }>Charges: { quantityDelivery }</b>
                                    </div>
                                    <div className="col-lg-4 mb-3">
                                        <b className="alert-success" style={ {borderRadius: '10px', padding: '10px', fontSize: '14px'} }>Total Charge Company: { totalChargeCompany +' $' }</b>
                                    </div>
                                </div>
                            </h5>
                            <div className="row form-group table-responsive">
                                <div className="col-lg-12">
                                    <table className="table table-hover table-condensed table-bordered">
                                        <thead>
                                            <tr>
                                                <th><b>DATE</b></th>
                                                <th><b>N° INVOICE</b></th>
                                                <th><b>COMPANY</b></th>
                                                <th><b>START DATE</b></th>
                                                <th><b>END DATE</b></th>
                                                <th><b>TOTAL DELIVERY</b></th>
                                                <th><b>TOTAL ADJUSTMENT</b></th>
                                                <th><b>TOTAL</b></th>
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

export default Charge;

if (document.getElementById('charge'))
{
    ReactDOM.render(<Charge />, document.getElementById('charge'));
}