import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'
import moment from 'moment'
import ReactLoading from 'react-loading';

function ReportReturnCompany() {

    const [listReport, setListReport]         = useState([]);
    const [listDeliveries, setListDeliveries] = useState([]);
    const [listTeam, setListTeam]             = useState([]);
    const [listDriver, setListDriver]         = useState([]);
    const [roleUser, setRoleUser]             = useState([]);
    const [listCompany , setListCompany]      = useState([]);
    const [listComment, setListComment]       = useState([]);

    const [quantityDispatch, setQuantityDispatch] = useState(0);

    const [listRoute, setListRoute]  = useState([]);
    const [listState , setListState] = useState([]);

    const [dateInit, setDateInit]       = useState(auxDateInit);
    const [dateEnd, setDateEnd]         = useState(auxDateInit);
    const [RouteSearch, setRouteSearch] = useState('all');
    const [StateSearch, setStateSearch] = useState('all');
    const [idCompany, setCompany]       = useState(0);

    const [Reference_Number_1, setReference_Number_1] = useState('');
    const [Description_Return, setDescriptionReturn]  = useState('');
    const [Weight, setWeight]                         = useState('');
    const [Width, setWidth]                           = useState('');
    const [Length, setLength]                         = useState('');
    const [Height, setHeight]                         = useState('');
    const [client, setClient]                         = useState('');

    const [page, setPage]                 = useState(1);
    const [totalPage, setTotalPage]       = useState(0);
    const [totalPackage, setTotalPackage] = useState(0);
    const [isLoading, setIsLoading]       = useState(false);

    const [file, setFile]             = useState('');
    const [btnDisplay, setbtnDisplay] = useState('none');

    const [viewButtonSave, setViewButtonSave] = useState('none');

    const inputFileRef  = React.useRef();

    const [readOnlyInput, setReadOnlyInput]   = useState(false);
    const [disabledButton, setDisabledButton] = useState(false);

    const [textButtonSave, setTextButtonSave] = useState('Guardar');

    useEffect( () => {

        listAllCompany();
        listAllRoute();
    }, []);

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

    useEffect(() => {

        listReturnCompany(1, RouteSearch, StateSearch);

    }, [dateInit, dateEnd, idCompany]);

    const onBtnClickFile = () => {

        setViewButtonSave('none');

        inputFileRef.current.click();
    }

    const listReturnCompany = (pageNumber, routeSearch, stateSearch) => {

        setIsLoading(true);
        setListReport([]);

        fetch(url_general +'report/return-company/list/'+ dateInit +'/'+ dateEnd +'/'+ idCompany +'/'+ routeSearch +'/'+ stateSearch +'?page='+ pageNumber)
        .then(res => res.json())
        .then((response) => {

            setIsLoading(false);
            setListReport(response.packageReturnCompanyList.data);
            setTotalPackage(response.packageReturnCompanyList.total);
            setTotalPage(response.packageReturnCompanyList.per_page);
            setPage(response.packageReturnCompanyList.current_page);
            setQuantityDispatch(response.packageReturnCompanyList.total);

            setRoleUser(response.roleUser);
            setListState(response.listState);

            if(listState.length == 0)
            {
                listOptionState(response.listState);
            }

            if(response.roleUser == 'Administrador')
            {
                //listAllTeam();
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

        listReturnCompany(pageNumber, RouteSearch, StateSearch);
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
            location.href = url_general +'report/return-company/export/'+ dateInit +'/'+ dateEnd +'/'+ idCompany +'/'+ RouteSearch +'/'+ StateSearch;
        }
    }

    const listReportTable = listReport.map( (packageReturnCompany, i) => {

        return (

            <tr key={i}>
                <td style={ { width: '100px'} }>
                    { packageReturnCompany.created_at.substring(5, 7) }-{ packageReturnCompany.created_at.substring(8, 10) }-{ packageReturnCompany.created_at.substring(0, 4) }
                </td>
                <td>
                    { packageReturnCompany.created_at ? packageReturnCompany.created_at.substring(11, 19):'' }
                </td>
                <td>{ packageReturnCompany.company }</td>
                <td><b>{ packageReturnCompany.Reference_Number_1 }</b></td>
                <td>{ packageReturnCompany.Dropoff_Contact_Name }</td>
                <td>{ packageReturnCompany.Dropoff_Contact_Phone_Number }</td>
                <td>{ packageReturnCompany.Dropoff_Address_Line_1 }</td>
                <td>{ packageReturnCompany.Dropoff_City }</td>
                <td>{ packageReturnCompany.Dropoff_Province }</td>
                <td>{ packageReturnCompany.Dropoff_Postal_Code }</td>
                <td>{ packageReturnCompany.Route }</td>
                <td>{ packageReturnCompany.Description_Return }</td>
                <td>{ packageReturnCompany.client }</td>
                <td>{ packageReturnCompany.Weight }</td>
                <td>{ packageReturnCompany.Width }</td>
                <td>{ packageReturnCompany.Length }</td>
                <td>{ packageReturnCompany.Height }</td>

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

            listReturnCompany(1, routesSearch, StateSearch);
        }
        else
        {
            setRouteSearch('all');

            listReturnCompany(1, 'all', StateSearch);
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

            listReturnCompany(page, RouteSearch, statesSearch);
        }
        else
        {
            setStateSearch('all');

            listReturnCompany(page, RouteSearch, 'all');
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

    const handlerInsert = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('Reference_Number_1', Reference_Number_1);
        formData.append('client', client);
        formData.append('Description_Return', Description_Return);
        formData.append('Weight', Weight);
        formData.append('Width', Width);
        formData.append('Length', Length);
        formData.append('Height', Height);

        //clearValidation();

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        setDisabledButton(true);
        setTextButtonSave('Loading...');

        let url = 'report/return-company/insert'

        fetch(url_general + url, {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json()).
        then((response) => {

                setTextButtonSave('Guardar');
                setDisabledButton(false);

                if(response.stateAction == 'validatedFilterPackage')
                {
                    let packageBlocked  = response.packageBlocked;

                    Swal.fire({
                        icon: 'error',
                        title: 'PACKAGE BLOCKED #'+ Reference_Number_1,
                        text: packageBlocked.comment,
                        showConfirmButton: false,
                        timer: 2000,
                    });
                    
                    document.getElementById('soundPitidoBlocked').play();
                }
                else if(response.stateAction == 'commentNotExists')
                {
                    swal('The COMMENT does not exist #'+ Reference_Number_1, {

                        icon: "warning",
                    });

                    document.getElementById('soundPitidoWarning').play();
                }
                else if(response.stateAction == 'validatedLost')
                {
                    swal('THE PACKAGE WAS RECORDED BEFORE AS LOST #'+ Reference_Number_1, {

                        icon: "warning",
                    });

                    document.getElementById('soundPitidoWarning').play();
                }
                else if(response.stateAction == 'notExists')
                {
                    swal('Packet does not exist in Inbound or Dispatch!', {

                        icon: "warning",
                    });
                }
                else if(response.stateAction)
                {
                    swal('Package returned to the company!', {

                        icon: "success",
                    });

                    listReturnCompany(1, RouteSearch, StateSearch);
                    clearForm();
                }
                else(response.status == 422)
                {
                    for(const index in response.errors)
                    {
                        document.getElementById(index).style.display = 'block';
                        document.getElementById(index).innerHTML     = response.errors[index][0];
                    }
                }
            },
        );
    }

    const clearForm = () => {

        setReference_Number_1('');
        setDescriptionReturn('');
        setClient('');
        setMeasures('');
        setWeight('');
    }

    const handlerOpenModal = (PACKAGE_ID) => {

        let myModal = new bootstrap.Modal(document.getElementById('modalInsertReturn'), {

            keyboard: false,
            backdrop: 'static',
        });

        myModal.show();
        listAllComment('RTS');
    }

    const handlerImport = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('file', file);

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        LoadingShow();

        fetch(url_general +'report/return-company/import', {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json()).
        then((response) => {

                if(response.stateAction)
                {
                    swal("The file was imported!", {

                        icon: "success",
                    });

                    listReturnCompany(1, RouteSearch, StateSearch);
                }

                setViewButtonSave('none');

                LoadingHide();
            },
        );
    }

    const optionCompany = listCompany.map( (company, i) => {

        return <option value={company.id}>{company.name}</option>
    })

    const listAllComment = (category) => {

        fetch(url_general +'comments/get-all-by-category/'+ category)
        .then(res => res.json())
        .then((response) => { 

            setListComment(response.commentList);
        });
    }

    const optionsComment = listComment.map( (comment, i) => {

        return (
            (
                <option key={ i } value={ comment.description }> { comment.description }</option>
            )

        );
    });

    const modalInsertReturn = <React.Fragment>
                                    <div className="modal fade" id="modalInsertReturn" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog modal-md">
                                            <form onSubmit={ handlerInsert }>
                                                <div className="modal-content">
                                                    <div className="modal-header">
                                                        <h5 className="modal-title text-primary" id="exampleModalLabel">Register Return Company</h5>
                                                        <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div className="modal-body">
                                                        <div className="row">
                                                            <div className="col-lg-12">
                                                                <div className="form-group">
                                                                    <label className="form">PACKAGE ID</label>
                                                                    <div id="Reference_Number_1" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ Reference_Number_1 } className="form-control" onChange={ (e) => setReference_Number_1(e.target.value) } maxLength="25" required/>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div className="row">
                                                            <div className="col-lg-12">
                                                                <div className="form-group">
                                                                    <label className="form">COMMENT</label>
                                                                    <div id="Description_Return" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <select name="" id="" className="form-control" onChange={ (e) => setDescriptionReturn(e.target.value) } required>
                                                                        <option value="">Selection comment</option>
                                                                        { optionsComment }
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-12">
                                                                <div className="form-group">
                                                                    <label className="form">CLIENT</label>
                                                                    <div id="client" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ client } className="form-control" onChange={ (e) => setClient(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div className="row">
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label className="form">WEIGHT</label>
                                                                    <div id="Weight" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="number" value={ Weight } className="form-control" step="0.01" min="0" max="999" onChange={ (e) => setWeight(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label className="form">WIDTH</label>
                                                                    <div id="Width" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="number" value={ Width } className="form-control" step="0.01" min="0" max="999" onChange={ (e) => setWidth(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label className="form">LENGTH</label>
                                                                    <div id="Length" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="number" value={ Length } className="form-control" step="0.01" min="0" max="999" onChange={ (e) => setLength(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label className="form">HEIGHT</label>
                                                                    <div id="Height" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="number" value={ Height } className="form-control" step="0.01" min="0" max="999" onChange={ (e) => setHeight(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div className="modal-footer">
                                                        <button type="button" className="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                        <button className="btn btn-primary" disabled={ disabledButton }>Register Return</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </React.Fragment>;

    return (

        <section className="section">
            { modalInsertReturn }
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row form-group">
                                    <div className="col-lg-12 form-group">
                                        <div className="row form-group">
                                            <div className="col-lg-3">
                                                <label htmlFor="">Start date:</label>
                                                <input type="date" value={ dateInit } onChange={ (e) => handlerChangeDateInit(e.target.value) } className="form-control"/>
                                            </div>
                                            <div className="col-lg-3">
                                                <label htmlFor="">End date:</label>
                                                <input type="date" value={ dateEnd } onChange={ (e) => handlerChangeDateEnd(e.target.value) } className="form-control"/>
                                            </div>

                                            <div className="col-lg-3">
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

                                            <div className="col-lg-3">
                                                <div className="row">
                                                    <div className="col-lg-12">
                                                        State : 
                                                    </div>
                                                    <div className="col-lg-12">
                                                        <Select isMulti onChange={ (e) => handlerChangeState(e) } options={ optionsStateSearch } />
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="col-lg-3">
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
                                    <div className="col-lg-3 mb-3" style={ {paddingLeft: (isLoading ? '5%' : '')} }>
                                        {
                                            (
                                                isLoading
                                                ? 
                                                    <ReactLoading type="bubbles" color="#A8A8A8" height={20} width={50} />
                                                :
                                                    <b className="alert-success" style={ {borderRadius: '10px', padding: '10px', fontSize: '14px'} }>RETURN COMPANY: { quantityDispatch }</b>
                                            )
                                        }
                                    </div>
                                    <div className="col-lg-3">
                                        <button className="btn btn-success form-control" onClick={ () => handlerExport() }><i className="ri-file-excel-fill"></i> Export</button>
                                    </div>
                                    <div className="col-lg-3">
                                        <button className="btn btn-danger form-control" onClick={ () => handlerOpenModal() }> Return</button>
                                    </div>
                                    <div className="col-lg-3">
                                        <form onSubmit={ handlerImport }>
                                            <div className="form-group">
                                                <button type="button" className="btn btn-primary form-control" onClick={ () => onBtnClickFile() }>
                                                    <i className="bx bxs-file"></i> Import
                                                </button>
                                                <input type="file" id="fileImport" className="form-control" ref={ inputFileRef } style={ {display: 'none'} } onChange={ (e) => setFile(e.target.files[0]) } accept=".csv" required/>
                                            </div>
                                            <div className="form-group" style={ {display: viewButtonSave} }>
                                                <button className="btn btn-primary form-control" onClick={ () => handlerImport() }>
                                                    <i className="bx  bxs-save"></i> Save
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <audio id="soundPitidoBlocked" src="../sound/pitido-blocked.mp3" preload="auto"></audio>
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
                                                <th>ADDRESS</th>
                                                <th>CITY</th>
                                                <th>STATE</th>
                                                <th>ZIP CODE</th>
                                                <th>ROUTE</th>
                                                <th>DESCRIPTION</th>
                                                <th>CLIENT</th>
                                                <th>WEIGHT</th>
                                                <th>WIDTH</th>
                                                <th>LENGTH</th>
                                                <th>HEIGHT</th>
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

export default ReportReturnCompany;

if (document.getElementById('reportReturnCompany'))
{
    ReactDOM.render(<ReportReturnCompany />, document.getElementById('reportReturnCompany'));
}
