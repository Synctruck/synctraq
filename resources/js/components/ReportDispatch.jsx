import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'

function ReportDispatch() {

    const [listReport, setListReport] = useState([]);
    const [listTeam, setListTeam]     = useState([]);
    const [listDriver, setListDriver] = useState([]);
    const [roleUser, setRoleUser]     = useState([]);
    const [listCompany , setListCompany]  = useState([]);
    const [idCompany, setCompany] = useState(0);

    const [quantityDispatch, setQuantityDispatch] = useState(0);

    const [listRoute, setListRoute]  = useState([]);
    const [listState , setListState] = useState([]);

    const [dateInit, setDateInit] = useState(auxDateInit);
    const [dateEnd, setDateEnd]   = useState(auxDateInit);
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
        listAllCompany();

    }, []);

    useEffect(() => {

        listReportDispatch(1, RouteSearch, StateSearch);

    }, [dateInit, dateEnd, idTeam, idDriver,idCompany]);


    const listReportDispatch = (pageNumber, routeSearch, stateSearch) => {

        setListReport([]);

        fetch(url_general +'report/list/dispatch/'+ idCompany +'/'+ dateInit +'/'+ dateEnd +'/'+ idTeam +'/'+ idDriver +'/'+ routeSearch +'/'+ stateSearch +'?page='+ pageNumber)
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
    const optionCompany = listCompany.map( (company, i) => {

        return <option value={company.id}>{company.name}</option>
    })

    const handlerChangeDateEnd = (date) => {

        setDateEnd(date);
    }

    const handlerChangePage = (pageNumber) => {

        listReportDispatch(pageNumber, RouteSearch, StateSearch);
    }

    const handlerExport = () => {

        location.href = url_general +'report/export/dispatch/'+ idCompany +'/'+ dateInit +'/'+ dateEnd +'/'+ idTeam +'/'+ idDriver +'/'+ RouteSearch +'/'+ StateSearch;
    }

    const listReportTable = listReport.map( (packageDispatch, i) => {

        let team   = (packageDispatch.team ? packageDispatch.team.name : '');
        let driver = (packageDispatch.driver ? packageDispatch.driver.name +' '+ packageDispatch.driver.nameOfOwner : '');

        return (

            <tr key={i}>
                <td>
                    { packageDispatch.Date_Dispatch.substring(5, 7) }-{ packageDispatch.Date_Dispatch.substring(8, 10) }-{ packageDispatch.Date_Dispatch.substring(0, 4) }
                </td>
                <td>
                    { packageDispatch.Date_Dispatch.substring(11, 19) }
                </td>
                <td><b>{ packageDispatch.company }</b></td>
                {
                    roleUser == 'Administrador'
                    ?
                        <>
                            <td><b>{ team }</b></td>
                            <td><b>{ driver }</b></td>
                        </>


                    :
                        ''
                }
                {
                    roleUser == 'Team'
                    ?
                        <td><b>{ driver }</b></td>
                    :
                        ''
                }
                <td><b>{ packageDispatch.Reference_Number_1 }</b></td>
                <td>{ packageDispatch.Dropoff_Contact_Name }</td>
                <td>{ packageDispatch.Dropoff_Contact_Phone_Number }</td>
                <td>{ packageDispatch.Dropoff_Address_Line_1 }</td>
                <td>{ packageDispatch.Dropoff_City }</td>
                <td>{ packageDispatch.Dropoff_Province }</td>
                <td>{ packageDispatch.Dropoff_Postal_Code }</td>
                <td>{ packageDispatch.Weight }</td>
                <td>{ packageDispatch.Route }</td>
                <td>{ packageDispatch.taskOnfleet }</td>
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

    const handlerDownloadOnFleet = () => {

        var checkboxes = document.getElementsByName('checkDispatch');

        let countCheck = 0;

        let valuesCheck = '';

        for(var i = 0; i < checkboxes.length ; i++)
        {
            if(checkboxes[i].checked)
            {
                valuesCheck = (valuesCheck == '' ? checkboxes[i].value : valuesCheck +','+ checkboxes[i].value);

                countCheck++;
            }
        }

        let type = 'all';

        if(countCheck)
        {
            type = 'check'
        }

        if(valuesCheck == '')
        {
            valuesCheck = 'all';
        }

        location.href = url_general +'package/download/onfleet/'+ idTeam +'/'+ idDriver +'/'+ type +'/'+ valuesCheck +'/'+ StateSearch +'/day/'+ dateInit +'/'+ dateEnd;
    }

    const handlerDownloadRoadWarrior = () => {

        var checkboxes = document.getElementsByName('checkDispatch');

        let countCheck = 0;

        let valuesCheck = '';

        for(var i = 0; i < checkboxes.length ; i++)
        {
            if(checkboxes[i].checked)
            {
                valuesCheck = (valuesCheck == '' ? checkboxes[i].value : valuesCheck +','+ checkboxes[i].value);

                countCheck++;
            }
        }

        let type = 'all';

        if(countCheck)
        {
            type = 'check'
        }

        if(valuesCheck == '')
        {
            valuesCheck = 'all';
        }

        location.href = url_general +'package/download/roadwarrior/'+ idTeam +'/'+ idDriver +'/'+ type +'/'+ valuesCheck +'/'+ StateSearch +'/day/'+ dateInit +'/'+ dateEnd;
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
                                            <div className="col-lg-2" style={ {display: 'none'} }>
                                                <div className="form-group">
                                                    <button className="btn btn-info btn-sm form-control" style={ {background: '#6b60ab', border: '1px solid #6b60ab', color: 'white', display: 'none'} }  onClick={ handlerDownloadOnFleet }>ONFLEET</button>
                                                </div>
                                            </div>
                                            <div className="col-lg-2">
                                                <div className="form-group">
                                                    <button className="btn btn-danger btn-sm form-control" onClick={ handlerDownloadRoadWarrior }>ROADW</button>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="row form-group">

                                            <div className="col-lg-2">
                                                <label htmlFor="">Fecha de inicio:</label>
                                                <input type="date" value={ dateInit } onChange={ (e) => handlerChangeDateInit(e.target.value) } className="form-control"/>
                                            </div>
                                            <div className="col-lg-2">
                                                <label htmlFor="">Fecha final:</label>
                                                <input type="date" value={ dateEnd } onChange={ (e) => handlerChangeDateEnd(e.target.value) } className="form-control"/>
                                            </div>
                                            <dvi className="col-lg-2">
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
                                                <th>COMPANY</th>
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
                                                <th>TASK ONFLEET</th>
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

export default ReportDispatch;

if (document.getElementById('reportDispatch'))
{
    ReactDOM.render(<ReportDispatch />, document.getElementById('reportDispatch'));
}
