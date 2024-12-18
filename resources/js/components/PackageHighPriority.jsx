import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'
import moment from 'moment'
import ReactLoading from 'react-loading';

function PackageHighPriority() {

    const [listReport, setListReport]                   = useState([]);

    const [dateInit, setDateInit] = useState(auxDateInit);
    const [dateEnd, setDateEnd]   = useState(auxDateInit);

    const [quantityInbound, setQuantityInbound] = useState(0);

    const [listRoute, setListRoute]  = useState([]);
    const [listState , setListState] = useState([]);

    const [listCompany , setListCompany]  = useState([]);
    const [idCompany, setCompany]         = useState(0);

    const [RouteSearch, setRouteSearch] = useState('all');
    const [StateSearch, setStateSearch] = useState('all');

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

    }, [dateInit, dateEnd, idCompany]);


    const listReportInbound = (pageNumber, stateSearch, routeSearch) => {

        setIsLoading(true);

        fetch(url_general +'package-high-priority/list/'+  idCompany +'/'+ stateSearch +'/'+ routeSearch +'?page='+ pageNumber)
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

    const handlerExport = (type) => {
        
        let url = url_general +'package-high-priority/export/'+ idCompany +'/'+ StateSearch +'/'+ RouteSearch +'/'+ type;

        if(type == 'download')
        {
            location.href = url;
        }
        else
        {
            setIsLoading(true);

            fetch(url)
            .then(res => res.json())
            .then((response) => {

                if(response.stateAction == true)
                {
                    swal("The export was sended to your mail!", {

                        icon: "success",
                    });
                }
                else
                {
                    swal("There was an error, try again!", {

                        icon: "error",
                    });
                }

                setIsLoading(false);
            });
        }
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
                <td>{ packageInbound.internal_comment }</td>
                <td>
                    <div className="alert alert-danger">
                        <b>HIGH</b>
                    </div>
                </td>
                <td>{ packageInbound.status }</td>
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
                                        <button className="btn btn-success btn-sm form-control" onClick={ () => handlerExport('download') }><i className="ri-file-excel-fill"></i> EXPORT</button>
                                    </div>
                                    <div className="col-3">
                                        <button className="btn btn-warning btn-sm form-control text-white" onClick={  () => handlerExport('send') }>
                                            <i className="ri-file-excel-fill"></i> EXPORT TO THE MAIL
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
                                                <th>INTERNAL COMMENT</th>
                                                <th>PRIORITY</th>
                                                <th>ACTUAL STATUS</th>
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

export default PackageHighPriority;

// DOM element
if (document.getElementById('packageHighPriority')) {
    ReactDOM.render(<PackageHighPriority />, document.getElementById('packageHighPriority'));
}