import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'
import moment from 'moment/moment'

function ReportManifest() {

    const [listReport, setListReport] = useState([]);

    const [dateInit, setDateInit] = useState(auxDateInit);
    const [dateEnd, setDateEnd]   = useState(auxDateInit);

    const [quantityManifest, setQuantityManifest] = useState(0);

    const [listRoute, setListRoute]      = useState([]);
    const [listState , setListState]     = useState([]);
    const [listCompany , setListCompany] = useState([]);

    const [idCompany, setCompany]       = useState(0);
    const [RouteSearch, setRouteSearch] = useState('all');
    const [StateSearch, setStateSearch] = useState('all');

    const [page, setPage]                 = useState(1);
    const [totalPage, setTotalPage]       = useState(0);
    const [totalPackage, setTotalPackage] = useState(0);

    useEffect( () => {

        listAllRoute();
        listAllCompany();

    }, []);

    useEffect(() => {

        listReportManifest(page, RouteSearch, StateSearch);

    }, [ idCompany, dateInit, dateEnd ]);


    const listReportManifest = (pageNumber, routeSearch, stateSearch) => {

        fetch(url_general +'report/list/manifest/'+ idCompany +'/'+ dateInit +'/'+ dateEnd +'/'+ routeSearch +'/'+ stateSearch +'?page='+ pageNumber)
        .then(res => res.json())
        .then((response) => {

            setListReport(response.listAll.data);
            setTotalPackage(response.listAll.total);
            setTotalPage(response.listAll.per_page);
            setPage(response.listAll.current_page);
            setQuantityManifest(response.listAll.total);
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

            setListCompany([{id:0,name:"ALL"},...response.companyList]);
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

        listReportManifest(pageNumber, RouteSearch, StateSearch);
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
            location.href = url_general +'report/export/manifest/'+ dateInit +'/'+ dateEnd +'/'+ RouteSearch +'/'+ StateSearch;
        }
    }

    const listReportTable = listReport.map( (pack, i) => {

        return (

            <tr key={i} className="alert-success">
                <td>
                    { pack.created_at.substring(5, 7) }-{ pack.created_at.substring(8, 10) }-{ pack.created_at.substring(0, 4) }
                </td>
                <td>
                    { pack.created_at.substring(11, 19) }
                </td>
                <td><b>{ pack.company }</b></td>
                <td><b>{ pack.Reference_Number_1 }</b></td>
                <td>{ pack.Dropoff_Contact_Name }</td>
                <td>{ pack.Dropoff_Contact_Phone_Number }</td>
                <td>{ pack.Dropoff_Address_Line_1 }</td>
                <td>{ pack.Dropoff_City }</td>
                <td>{ pack.Dropoff_Province }</td>
                <td>{ pack.Dropoff_Postal_Code }</td>
                <td>{ pack.Weight }</td>
                <td>{ pack.Route }</td>
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

            listReportManifest(1, routesSearch, StateSearch);
        }
        else
        {
            setRouteSearch('all');

            listReportManifest(1, 'all', StateSearch);
        }
    };

    const [optionsRoleSearch, setOptionsRoleSearch] = useState([]);

    const listOptionRoute = (listRoutes) => {

        setOptionsRoleSearch([]);

        listRoutes.map( (route, i) => {

            optionsRoleSearch.push({ value: route.name, label: route.name });

            setOptionsRoleSearch(optionsRoleSearch);
        });

        console.log(optionsRoleSearch);
    }

    const handlerChangeState = (states) => {

        if(states.length != 0)
        {
            let statesSearch = '';

            states.map( (state) => {

                statesSearch = statesSearch == '' ? state.value : statesSearch +','+ state.value;
            });

            setStateSearch(statesSearch);

            listReportManifest(1, RouteSearch, statesSearch);
        }
        else
        {
            setStateSearch('all');

            listReportManifest(1, RouteSearch, 'all');
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
                                        <button className="btn btn-success btn-sm form-control" onClick={ () => handlerExport() }><i className="ri-file-excel-fill"></i> Export</button>
                                    </div>
                                </div>
                                <div className="row">
                                    <div className="col-lg-2 form-group">
                                        <b className="alert-success" style={ {borderRadius: '10px', padding: '10px'} }>Manifest: { quantityManifest }</b>
                                    </div>
                                    <div className="col-lg-2">
                                        <label htmlFor="">Start date:</label>
                                        <input type="date" value={ dateInit } onChange={ (e) => handlerChangeDateInit(e.target.value) } className="form-control"/>
                                    </div>
                                    <div className="col-lg-2">
                                        <label htmlFor="">End date:</label>
                                        <input type="date" value={ dateEnd } onChange={ (e) => handlerChangeDateEnd(e.target.value) } className="form-control"/>
                                    </div>
                                    <div className="col-lg-2">
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
                                    </div>
                                    <div className="col-lg-2">
                                        <div className="row">
                                            <div className="col-lg-12">
                                                State :
                                            </div>
                                            <div className="col-lg-12">
                                                <Select isMulti onChange={ (e) => handlerChangeState(e) } options={ optionsStateSearch } />
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-lg-2">
                                        <div className="row">
                                            <div className="col-lg-12">
                                                Route :
                                            </div>
                                            <div className="col-lg-12">
                                                <Select isMulti onChange={ (e) => handlerChangeRoute(e) } options={ optionsRoleSearch } />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </h5>
                            <div className="row form-group table-responsive">
                                <div className="col-lg-12">
                                    <table className="table table-hover table-condensed table-bordered">
                                        <thead>
                                            <tr>
                                                <th>DATE</th>
                                                <th>HOUR</th>
                                                <th>COMPANY</th>
                                                <th>PACKAGE ID</th>
                                                <th>CLIENT</th>
                                                <th>CONTACT</th>
                                                <th>ADDREESS</th>
                                                <th>CITY</th>
                                                <th>STATE</th>
                                                <th>ZIP C</th>
                                                <th>WEIGHT</th>
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

export default ReportManifest;

if (document.getElementById('reportManifest'))
{
    ReactDOM.render(<ReportManifest />, document.getElementById('reportManifest'));
}
