import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'
import moment from 'moment'


function PackageAge() {

    const [listReport, setListReport]                   = useState([]);

    const [dateInit, setDateInit] = useState(auxDateInit);
    const [dateEnd, setDateEnd]   = useState(auxDateInit);

    const [quantityInbound, setQuantityInbound] = useState(0);

    const [listRoute, setListRoute]  = useState([]);
    const [listState , setListState] = useState([]);
    const [listTruck , setListTruck] = useState([]);

    const [listCompany , setListCompany]  = useState([]);
    const [idCompany, setCompany] = useState(0);

    const [RouteSearch, setRouteSearch] = useState('all');
    const [StateSearch, setStateSearch] = useState('all');
    const [truckSearch, setTruckSearch] = useState('all');

    const [page, setPage]                 = useState(1);
    const [totalPage, setTotalPage]       = useState(0);
    const [totalPackage, setTotalPackage] = useState(0);

    useEffect( () => {

        listAllRoute();
        listAllCompany();

    }, []);

    useEffect(() => {

        listReportInbound(page, RouteSearch, StateSearch,truckSearch);

    }, [dateInit, dateEnd, idCompany]);


    const listReportInbound = (pageNumber, routeSearch, stateSearch,truckSearch ) => {

        fetch(url_general +'package-age/list/'+ routeSearch +'/'+ stateSearch +'?page='+ pageNumber)
        .then(res => res.json())
        .then((response) => {

            setListReport(response.listAll);
            setTotalPackage(response.packageHistoryList.total);
            setTotalPage(response.packageHistoryList.per_page);
            setPage(response.packageHistoryList.current_page);
            setQuantityInbound(response.packageHistoryList.total);

            setListState(response.listState);

            if(listState.length == 0)
            {
                listOptionState(response.listState);
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

    const optionCompany = listCompany.map( (company, i) => {

        return <option value={company.id}>{company.name}</option>
    })

    const listAllRoute = () => {

        setListRoute([]);

        fetch(url_general +'routes/getAll')
        .then(res => res.json())
        .then((response) => {

            setListRoute(response.routeList);
            listOptionRoute(response.routeList);
        });
    }

    const handlerChangePage = (pageNumber) => {

        listReportInbound(pageNumber, RouteSearch, StateSearch);
    }

    const handlerExport = () => {
        let date1= moment(dateInit);
        let date2 = moment(dateEnd);
        let difference = date2.diff(date1,'days');

        if(difference> limitToExport){
            swal(`Maximum limit to export is ${limitToExport} days`, {
                icon: "warning",
            });
        }else{
            location.href = url_general +'report/export/inbound/'+ idCompany +'/'+ dateInit +'/'+ dateEnd +'/'+ RouteSearch +'/'+ StateSearch+'/'+ truckSearch;
        }
    }

    const listReportTable = listReport.map( (packageInbound, i) => {

        return (

            <tr key={i}>
                <td>
                    { packageInbound.created_at.substring(5, 7) }-{ packageInbound.created_at.substring(8, 10) }-{ packageInbound.created_at.substring(0, 4) }
                </td>
                <td className="text-center"><b>{ packageInbound.lateDays }</b></td>
                <td><b>{ packageInbound.Reference_Number_1 }</b></td>
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

    const handlerChangeRoute = (routes) => {

        if(routes.length != 0)
        {
            let routesSearch = '';

            routes.map( (route) => {

                routesSearch = routesSearch == '' ? route.value : routesSearch +','+ route.value;
            });

            setRouteSearch(routesSearch);

            listReportInbound(1, routesSearch, StateSearch);
        }
        else
        {
            setRouteSearch('all');

            listReportInbound(1, 'all', StateSearch);
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

            listReportInbound(1, RouteSearch, statesSearch);
        }
        else
        {
            setStateSearch('all');

            listReportInbound(1, RouteSearch, 'all');
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

    return (

        <section className="section">
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row form-group">
                                    <div className="col-lg-2">
                                        <b className="alert-success" style={ {borderRadius: '10px', padding: '10px'} }>Packages: { quantityInbound }</b>
                                    </div>
                                    <div className="col-lg-2">
                                        <button className="btn btn-success btn-sm form-control" onClick={ () => handlerExport() }><i className="ri-file-excel-fill"></i> Export</button>
                                    </div>
                                    <div className="col-lg-4">
                                        <div className="row">
                                            <div className="col-lg-3">
                                                State :
                                            </div>
                                            <div className="col-lg-9">
                                                <Select isMulti onChange={ (e) => handlerChangeState(e) } options={ optionsStateSearch } />
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-lg-4">
                                        <div className="row">
                                            <div className="col-lg-3">
                                                Route :
                                            </div>
                                            <div className="col-lg-9">
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
                                                <th>PACKAGE ID</th>
                                                <th>CLIENT</th>
                                                <th>CONTACT</th>
                                                <th>ADDREESS</th>
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
