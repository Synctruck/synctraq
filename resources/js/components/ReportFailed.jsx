import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'

function ReportFailed() {

    const [listReport, setListReport] = useState([]);
    const [listTeam, setListTeam]     = useState([]);
    const [listDriver, setListDriver] = useState([]);
    const [roleUser, setRoleUser]     = useState([]);

    const [quantityDispatch, setQuantityDispatch] = useState(0);

    const [listRoute, setListRoute]  = useState([]);
    const [listState , setListState] = useState([]);

    const [dateInit, setDateInit] = useState(auxDateInit);
    const [dateEnd, setDateEnd]   = useState(auxDateEnd);
    const [idTeam, setIdTeam]     = useState(0);
    const [idDriver, setIdDriver] = useState(0);

    const [RouteSearch, setRouteSearch] = useState('all');
    const [StateSearch, setStateSearch] = useState('all');

    const [page, setPage]                 = useState(1);
    const [totalPage, setTotalPage]       = useState(0);
    const [totalPackage, setTotalPackage] = useState(0);

    useEffect( () => {

        listAllTeam();
        listAllRoute();

    }, []);

    useEffect(() => {

        listReportFailed(1, RouteSearch, StateSearch);

    }, [dateInit, dateEnd, idTeam, idDriver]);


    const listReportFailed = (pageNumber, routeSearch, stateSearch) => {

        setListReport([]);

        fetch(url_general +'report/list/failed/'+ dateInit +'/'+ dateEnd +'/'+ idTeam +'/'+ idDriver +'/'+ routeSearch +'/'+ stateSearch +'?page='+ pageNumber)
        .then(res => res.json())
        .then((response) => {

            setListReport(response.reportList.data);
            setTotalPackage(response.reportList.total);
            setTotalPage(response.reportList.per_page);
            setPage(response.reportList.current_page);
            setQuantityDispatch(response.reportList.total);
            
            setRoleUser(response.roleUser);
            setListState(response.listState);

            if(listState.length == 0)
            {
                listOptionState(response.listState);
            }

            if(response.roleUser == 'Administrador')
            {
                listAllTeam();
            }
            else
            {
                listAllDriverByTeam(idUserGeneral);
                setIdTeam(idUserGeneral);
            }
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

        listReportFailed(pageNumber, RouteSearch, StateSearch);
    }

    const handlerExport = () => {
        
        location.href = url_general +'report/export/failed/'+ dateInit +'/'+ dateEnd +'/'+ idTeam +'/'+ idDriver +'/'+ RouteSearch +'/'+ StateSearch;
    }

    const listReportTable = listReport.map( (pack, i) => {

        return (

            <tr key={i} className="alert-success">
                <td>
                    { pack.Date_Failed.substring(5, 7) }-{ pack.Date_Failed.substring(8, 10) }-{ pack.Date_Failed.substring(0, 4) }
                </td>
                <td>
                    { pack.Date_Failed.substring(11, 19) }
                </td>
                {
                    roleUser == 'Administrador' 
                    ?
                        pack.driver ? parseInt(pack.driver.idTeam) == 0 || pack.driver.idTeam == null ? <><td><b>{ pack.driver.name }</b></td><td><b></b></td></> : <><td><b>{ pack.driver.nameTeam }</b></td><td><b>{ pack.driver.name +' '+ pack.driver.nameOfOwner }</b></td></> : ''
                    :
                        ''
                }
                {
                    roleUser == 'Team' 
                    ?
                        pack.driver.idTeam ? <td><b>{ pack.driver.name +' '+ pack.driver.nameOfOwner }</b></td> : <td></td>
                    :
                        ''
                }
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

    const listTeamSelect = listTeam.map( (team, i) => {

        return (

            <option value={ team.id }>{ team.name }</option>
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

            listReportFailed(1, routesSearch, StateSearch);
        }
        else
        {
            setRouteSearch('all');

            listReportFailed(1, 'all', StateSearch);
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

            listReportFailed(page, RouteSearch, statesSearch);
        }
        else
        {
            setStateSearch('all');

            listReportFailed(page, RouteSearch, 'all');
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
                                    <div className="col-lg-12 form-group">
                                        <div className="row form-group">
                                            <div className="col-lg-2">
                                                <label htmlFor="">Fecha de inicio:</label>
                                                <input type="date" value={ dateInit } onChange={ (e) => handlerChangeDateInit(e.target.value) } className="form-control"/>
                                            </div>
                                            <div className="col-lg-2">
                                                <label htmlFor="">Fecha final:</label>
                                                <input type="date" value={ dateEnd } onChange={ (e) => handlerChangeDateEnd(e.target.value) } className="form-control"/>
                                            </div>
                                            {
                                                roleUser == 'Administrador'
                                                ?
                                                    <>
                                                        <div className="col-lg-2">
                                                            <div className="form-group">
                                                                <label htmlFor="">TEAM</label>
                                                                <select name="" id="" className="form-control" onChange={ (e) => listAllDriverByTeam(e.target.value) } required>
                                                                   <option value="0">Todos</option> 
                                                                    { listTeamSelect }
                                                                </select> 
                                                            </div>
                                                        </div>
                                                        <div className="col-lg-2">
                                                            <div className="form-group">
                                                                <label htmlFor="">DRIVER</label>
                                                                <select name="" id="" className="form-control" onChange={ (e) => setIdDriver(e.target.value) } required>
                                                                   <option value="0">Todos</option> 
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
                                                        <div className="col-lg-3">
                                                            <div className="form-group">
                                                                <label htmlFor="">DRIVER</label>
                                                                <select name="" id="" className="form-control" onChange={ (e) => setIdDriver(e.target.value) } required>
                                                                   <option value="0">Todos</option> 
                                                                    { listDriverSelect }
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </>
                                                :
                                                    ''
                                            }
                                            
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
                                    </div>
                                </div>
                                <div className="row">
                                    <div className="col-lg-2">
                                        <b className="alert-success" style={ {borderRadius: '10px', padding: '10px'} }>Dispatch: { quantityDispatch }</b> 
                                    </div>
                                    <div className="col-lg-2">
                                        <button className="btn btn-success btn-sm form-control" onClick={ () => handlerExport() }><i className="ri-file-excel-fill"></i> Export</button>
                                    </div>
                                </div>
                            </h5>
                            <div className="row form-group table-responsive">
                                <div className="col-lg-12">
                                    <table className="table table-hover table-condensed table-bordered">
                                        <thead> 
                                            <tr>
                                                <th>FECHA</th>
                                                <th>HORA</th>
                                                {
                                                    roleUser == 'Administrador'
                                                    ?
                                                        <th><b>TEAM</b></th>
                                                    :
                                                        ''
                                                }
                                                {
                                                    roleUser == 'Administrador'
                                                    ?
                                                        <th><b>DRIVER</b></th>
                                                    :
                                                         
                                                        roleUser == 'Team' ? <th><b>DRIVER</b></th> : ''
                                                }
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
                                        firstPageText="Primero"
                                        lastPageText="Último"
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

export default ReportFailed;

if (document.getElementById('reportFailed'))
{
    ReactDOM.render(<ReportFailed />, document.getElementById('reportFailed'));
}