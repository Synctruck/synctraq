import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'
import moment from 'moment'
import ReactLoading from 'react-loading';

function PackageAge() {

    const [listReport, setListReport] = useState([]);

    const [dateInit, setDateInit] = useState(auxDateInit);
    const [dateEnd, setDateEnd]   = useState(auxDateInit);

    const [quantityInbound, setQuantityInbound] = useState(0);

    const [listRoute, setListRoute]  = useState([]);
    const [listState , setListState] = useState([]);

    const [listCompany , setListCompany]  = useState([]);
    const [idCompany, setCompany]         = useState(0);

    const [RouteSearch, setRouteSearch]   = useState('all');
    const [StateSearch, setStateSearch]   = useState('all');
    const [StatusSearch, setStatusSearch] = useState('all');

    const [page, setPage]                 = useState(1);
    const [totalPage, setTotalPage]       = useState(0);
    const [totalPackage, setTotalPackage] = useState(0);
    const [isLoading, setIsLoading]       = useState(false);

    useEffect( () => {

        listAllCompany();
        listFilter();

    }, []);

    useEffect(() => {

        listReportInbound(page, StateSearch, RouteSearch);

    }, [dateInit, dateEnd, idCompany, StatusSearch]);


    const listReportInbound = (pageNumber, stateSearch, routeSearch) => {

        setIsLoading(true);

        fetch(url_general +'package-age/list/'+  idCompany +'/'+ stateSearch +'/'+ routeSearch +'/'+ StatusSearch +'?page='+ pageNumber)
        .then(res => res.json())
        .then((response) => {

            setIsLoading(false);
            setListReport(response.listAll);
            setTotalPackage(response.packageHistoryList.total);
            setTotalPage(response.packageHistoryList.per_page);
            setPage(response.packageHistoryList.current_page);
            setQuantityInbound(response.packageHistoryList.total);
        });
    }

    const listFilter = () => {

        fetch(url_general +'routes/filter/list')
        .then(res => res.json())
        .then((response) => { 

            setListState(response.listState);
            setListRoute(response.listRoute);

            listOptionState(response.listState);
            listOptionRoute(response.listRoute);
        });
    }

    const listAllCompany = () => {

        setListCompany([]);

        fetch(url_general +'company/getAll')
        .then(res => res.json())
        .then((response) => {

            setListCompany([{id:0,name:"ALL"},...response.companyList]);
        });
    }

    const handlerChangePage = (pageNumber) => {

        listReportInbound(pageNumber, StateSearch, RouteSearch);
    }

    const handlerExport = () => {
        
        location.href = url_general +'package-age/export/'+ idCompany +'/'+ StateSearch +'/'+ RouteSearch +'/'+ StatusSearch;
    }

    const listReportTable = listReport.map( (packageInbound, i) => {

        return (

            <tr key={i}>
                <td>
                    { packageInbound.created_at.substring(5, 7) }-{ packageInbound.created_at.substring(8, 10) }-{ packageInbound.created_at.substring(0, 4) }
                </td>
                <td className="text-center"><b>{ packageInbound.lateDays }</b></td>
                <td><b>{ packageInbound.company }</b></td>
                <td><b>{ packageInbound.Reference_Number_1 }</b></td>
                <td>{ packageInbound.status }</td>
                <td>
                    { packageInbound.statusDate.substring(5, 7) }-{ packageInbound.statusDate.substring(8, 10) }-{ packageInbound.statusDate.substring(0, 4) }
                </td>
                <td>{ packageInbound.statusDescription }</td>
                <td>{ packageInbound.Dropoff_Contact_Name }</td>
                <td>{ packageInbound.Dropoff_Contact_Phone_Number }</td>
                <td>{ packageInbound.Dropoff_Address_Line_1 }</td>
                <td>{ packageInbound.Dropoff_City }</td>
                <td>{ packageInbound.Dropoff_Province }</td>
                <td>{ packageInbound.Dropoff_Postal_Code }</td>
                <td>{ packageInbound.Route }</td>
            </tr>
        );
    });

    const [optionsStateSearch, setOptionsStateSearch] = useState([]);
    const [optionsRoleSearch, setOptionsRoleSearch] = useState([]);

    const listOptionState = (listStates) => {

        setOptionsRoleSearch([]);

        listStates.map( (state, i) => {

            optionsStateSearch.push({ value: state.state, label: state.state });

            setOptionsStateSearch(optionsStateSearch);
        });
    }

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

            listReportInbound(1, statesSearch, RouteSearch);
        }
        else
        {
            setStateSearch('all');

            listReportInbound(1, 'all', RouteSearch);
        }
    };

    const handlerChangeRoute = (routes) => {

        if(routes.length != 0)
        {
            let routesSearch = '';

            routes.map( (state) => {

                routesSearch = routesSearch == '' ? state.value : routesSearch +','+ state.value;
            });

            setRouteSearch(routesSearch);

            listReportInbound(1, StateSearch, routesSearch);
        }
        else
        {
            setRouteSearch('all');

            listReportInbound(1, StateSearch, 'all');
        }
    };

    const optionCompany = listCompany.map( (company, i) => {

        return <option value={company.id}>{company.name}</option>
    })

    return (

        <section className="section">
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row form-group">
                                    <div className="col-lg-2 form-group">
                                        <button className="btn btn-success btn-sm form-control" onClick={ () => handlerExport() }>
                                            <i className="ri-file-excel-fill"></i> EXPORT
                                        </button>
                                    </div>
                                </div>

                                <div className="row form-group">
                                    <div className="col-lg-2 form-group" style={ {paddingLeft: (isLoading ? '5%' : '')} }>
                                        {
                                            (
                                                isLoading
                                                ? 
                                                    <ReactLoading type="bubbles" color="#A8A8A8" height={20} width={50} />
                                                :
                                                    <b className="alert-success" style={ {borderRadius: '10px', padding: '10px'} }>Packages: { quantityInbound }</b>
                                            )
                                        }
                                    </div>
                                    <div className="col-lg-2">
                                        <div className="row">
                                            <div className="col-lg-12">
                                                <div className="form-group">
                                                    Company:
                                                </div>
                                            </div>
                                            <div className="col-lg-12">
                                                <select name="" id="" className="form-control" onChange={ (e) => setCompany(e.target.value) }>
                                                    <option value="" style={ {display: 'none'} }>Select...</option>
                                                    { optionCompany }
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-lg-2">
                                        <div className="row">
                                            <div className="col-lg-12 form-group">
                                                State :
                                            </div>
                                            <div className="col-lg-12 form-group">
                                                <Select isMulti onChange={ (e) => handlerChangeState(e) } options={ optionsStateSearch } />
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-lg-2">
                                        <div className="row">
                                            <div className="col-lg-12 form-group">
                                                Route :
                                            </div>
                                            <div className="col-lg-12 form-group">
                                                <Select isMulti onChange={ (e) => handlerChangeRoute(e) } options={ optionsRoleSearch } />
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-lg-2">
                                        <div className="row">
                                            <div className="col-lg-12 form-group">
                                                STATUS:
                                            </div>
                                            <div className="col-lg-12 form-group">
                                                <select name="" id="" className="form-control" onChange={ (e) => setStatusSearch(e.target.value) }>
                                                    <option value="all">All</option>
                                                    <option value="Inbound">Inbound</option>
                                                    <option value="Warehouse">Warehouse</option>
                                                    <option value="Dispatch">Dispatch</option>
                                                    <option value="Delete">Delete</option>
                                                    <option value="Failed">Failed</option>
                                                    <option value="NMI">NMI</option>
                                                    <option value="Middle Mile Scan">Middle Mile Scan</option>
                                                    <option value="LM Carrier">LM Carrier</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </h5>
                            <div className="row form-group table-responsive">
                                <div className="col-lg-12">
                                    <table className="table table-hover table-condensed table-bordered" style={ {fontSize: '17px'} }>
                                        <thead>
                                            <tr>
                                                <th>DATE</th>
                                                <th>LATE DAYS</th>
                                                <th>COMPANY</th>
                                                <th>PACKAGE ID</th>
                                                <th>ACTUAL STATUS</th>
                                                <th>STATUS DATE</th>
                                                <th>STATUS DESCRIPTION</th>
                                                <th>CLIENT</th>
                                                <th>CONTACT</th>
                                                <th>ADDRESS</th>
                                                <th>CITY</th>
                                                <th>STATE</th>
                                                <th>ZIP C</th>
                                                <th>ROUTE</th>
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

export default PackageAge;

// DOM element
if (document.getElementById('packageAge')) {
    ReactDOM.render(<PackageAge />, document.getElementById('packageAge'));
}
