import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'
import moment from 'moment';
import ReactLoading from 'react-loading';

let count = 1;

function PackageRts() {

    const [palletList, setPalletList]    = useState([]);
    const [listPackage, setListPackage]  = useState([]);
    const [listTeam, setListTeam]        = useState([]);
    const [listDriver, setListDriver]    = useState([]);
    const [roleUser, setRoleUser]        = useState([]);
    const [listRoute, setListRoute]      = useState([]);
    const [listRole, setListRole]        = useState([]);
    const [listState , setListState]     = useState([]);
    const [listCompany , setListCompany] = useState([]);

    const [id, setId]                                 = useState(0);
    const [idRole, setIdRole]                         = useState(0);
    const [name, setName]                             = useState('');
    const [nameOfOwner, setNameOfOwner]               = useState('');
    const [address, setAddress]                       = useState('');
    const [phone, setPhone]                           = useState('');
    const [email, setEmail]                           = useState('');
    const [idsRoutes, setIdsRoutes]                   = useState('');
    const [permissionDispatch, setPermissionDispatch] = useState(0);
    const [dayNight, setDayNight]                     = useState('');

    const [readOnlyPalet, setReadOnlyPalet] = useState(false);
    const [readOnly, setReadOnly]           = useState(false);
    const [checkAll, setCheckAll]           = useState(0);

    const [quantityDispatch, setQuantityDispatch]         = useState(0);
    const [quantityDispatchAll, setQuantityDispatchAll]   = useState(0);
    const [quantityFailed, setQuantityFailed]             = useState(0);
    const [quantityHighPriority, setQuantityHighPriority] = useState(0);

    // const [dataView, setDataView] = useState('today');
    const [statusPallet, setStatusPallet] = useState('');
    const [companyViewPallet, setCompanyViewPallet] = useState('');
    const [filterDispatch, setFilterDispatch] = useState('');
    const [PalletNumberForm, setPalletNumberForm] = useState('');
    const [dateStart, setDateStart] = useState(auxDateInit);
    const [dateEnd, setDateEnd]   = useState(auxDateInit);

    const [Reference_Number_1, setNumberPackage] = useState('');
    const [Description_Return, setDescription_Return] = useState('');
    const [client, setClient] = useState('');
    const [Weight, setWeight] = useState('');
    const [Width, setWidth] = useState('');
    const [Length, setLength] = useState('');
    const [Height, setHeight] = useState('');

    const [idTeam, setIdTeam] = useState(0);
    const [idDriver, setIdDriver] = useState(0);
    const [idDriverAsing, setIdDriverAsing] = useState(0);
    const [autorizationDispatch, setAutorizationDispatch] = useState(false);

    const [textMessage, setTextMessage]                 = useState('');
    const [textMessageDate, setTextMessageDate]         = useState('');
    const [typeMessageDispatch, setTypeMessageDispatch] = useState('');

    const [typeMessage, setTypeMessage] = useState('');

    const [file, setFile]             = useState('');

    const [page, setPage]                             = useState(1);
    const [totalPagePallet, setTotalPagePallet]       = useState(0);
    const [totalPackagePallet, setTotalPackagePallet] = useState(0);
    const [totalPage, setTotalPage]                   = useState(0);
    const [totalPackage, setTotalPackage]             = useState(0);

    const [CompanyPallet, setCompanyPallet] = useState('');

    const [RouteSearchList, setRouteSearchList] = useState('all');
    const [StateSearch, setStateSearch]         = useState('all');
    const [idCompany, setCompany]               = useState(0);
    const [isLoading, setIsLoading]             = useState(false);

    const inputFileRef  = React.useRef();

    const [viewButtonSave, setViewButtonSave] = useState('none');

    document.getElementById('bodyAdmin').style.backgroundColor = '#d1e7dd';

    useEffect(() => {

        listAllCompany();
        listAllTeam();

        document.getElementById('Reference_Number_1').focus();

    }, []);

    useEffect(() => {

        setPage(1);

        listAllPalet(1);
        //listAllPackageDispatch(1, StateSearch, RouteSearchList);

    }, [ dateStart, dateEnd ]);

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

    const listAllPalet = (pageNumber) => {

        setIsLoading(true);

        fetch(url_general +'pallet-rts/list/'+ dateStart +'/'+ dateEnd +'/?page='+ pageNumber)
        .then(res => res.json())
        .then((response) => {

            setIsLoading(false);
            setPalletList(response.palletList.data);
            setTotalPackagePallet(response.palletList.total);
            setTotalPagePallet(response.palletList.per_page);
            setPage(response.palletList.current_page);
        });
    }

    const listAllPackageDispatch = (pageNumber, StateSearch, RouteSearchList) => {

        fetch(url_general +'package-dispatch/list/'+ idCompany +'/'+ dateStart +'/'+ dateEnd +'/'+ idTeam +'/'+ idDriver +'/'+ StateSearch +'/'+ RouteSearchList +'/?page='+ pageNumber)
        .then(res => res.json())
        .then((response) => {

            setListPackageDispatch(response.packageDispatchList.data);
            setTotalPackage(response.packageDispatchList.total);
            setTotalPage(response.packageDispatchList.per_page);
            setPage(response.packageDispatchList.current_page);
            setQuantityDispatch(response.quantityDispatch);
            setQuantityDispatchAll(response.quantityDispatchAll);
            setQuantityFailed(response.quantityFailed);
            setQuantityHighPriority(response.quantityHighPriority);
            setRoleUser(response.roleUser);
            setListState(response.listState);

            if(listState.length == 0)
            {
                listOptionState(response.listState);
            }

            listAllTeam();
            /*if(response.roleUser == 'Master')
            {
                
            }
            else
            {
                listAllDriverByTeam(idUserGeneral);
                setIdTeam(idUserGeneral);
            }*/

            if(response.quantityDispatchAll > 0 || response.quantityFailed > 0)
            {
                setAutorizationDispatch(false);
            }
            else
            {
                setAutorizationDispatch(true);
            }
        });
    }

    const exportAllPackageDispatch = () => {
 
        location.href = url_general +'pallet-rts/export/'+ idCompany +'/'+ dateStart +'/'+ dateEnd;
    }

    const handlerExport = () => {
        // let date1= moment(dateStart);
        // let date2 = moment(dateEnd);
        // let difference = date2.diff(date1,'days');

        // if(difference> limitToExport){
        //     swal(`Maximum limit to export is ${limitToExport} days`, {
        //         icon: "warning",
        //     });
        // }else{

        // }

        exportAllPackageDispatch();
    }

    const handlerCreatePallet = () => {

        swal({
            title: "Want to create a new palette?",
            text: "",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {

            if(willDelete)
            {
                if(CompanyPallet != '')
                {
                    setIsLoading(true);

                    const formData = new FormData();

                    formData.append('idCompany', CompanyPallet);

                    let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    let url = 'pallet-rts/insert'

                    fetch(url_general + url, {
                        headers: { "X-CSRF-TOKEN": token },
                        method: 'post',
                        body: formData
                    })
                    .then(res => res.json()).
                    then((response) => {

                            setIsLoading(false);

                            if(response.stateAction)
                            {
                                swal('Palette created successfully!', {

                                    icon: "success",
                                });

                                listAllPalet(1);
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
                else
                {
                    swal('You must select a COMPANY!', {

                        icon: "warning",
                    });
                }
            }
        });
    }

    const handlerChangePage = (pageNumber) => {

        listAllPackageDispatch(pageNumber, StateSearch, RouteSearchList);
    }

    const listAllCompany = () => {

        setListCompany([]);

        fetch(url_general +'company/getAll')
        .then(res => res.json())
        .then((response) => {

            listOptionCompany(response.companyList);
        });
    }

    const listAllRoute = (pageNumber) => {

        setListRoute([]);

        fetch(url_general +'routes/getAll')
        .then(res => res.json())
        .then((response) => {

            setListRoute(response.routeList);
            listOptionRoute(response.routeList);
        });
    }

    const [Route, setRoute] = useState('');

    const [readOnlyInput, setReadOnlyInput]   = useState(false);
    const [disabledButton, setDisabledButton] = useState(false);

    const [textButtonSave, setTextButtonSave] = useState('Guardar');

    const optionsRole = listRoute.map( (route, i) => {

        return (

            <option key={ i } value={ route.name } selected={ Route == route.name ? true : false }> {route.name}</option>
        );
    });

    const optionCompany = listCompany.map( (company, i) => {

        return <option value={company.id}>{company.name}</option>
    })

    const handlerClosePallete = () => {

        swal({
            title: "Want to close the palette?",
            text: "",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {

            if(willDelete)
            {
                const formData = new FormData();

                formData.append('numberPallet', PalletNumberForm);

                let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                let url = 'package-pre-rts/chage-to-return-company';

                fetch(url_general + url, {
                    headers: { "X-CSRF-TOKEN": token },
                    method: 'post',
                    body: formData
                })
                .then(res => res.json())
                .then((response) => {

                    if(response.stateAction == true)
                    {
                        swal("The palette was return company correctly!", {

                            icon: "success",
                        });

                        listAllPalet(page);
                        listPackagePreDispatch(PalletNumberForm);
                    }
                    else
                    {
                        swal("There was a problem trying to close the palette, please try again!", {

                            icon: "warning",
                        });
                    }
                });
            }
        });
    }

    const listPackageDispatchTable = listPackage.map( (packagePreDispatch, i) => {

        return (

            <tr key={i}>
                <td style={ { width: '100px'} }>
                    { packagePreDispatch.created_at.substring(5, 7) }-{ packagePreDispatch.created_at.substring(8, 10) }-{ packagePreDispatch.created_at.substring(0, 4) }
                </td>
                <td>
                    { packagePreDispatch.created_at.substring(11, 19) }
                </td>
                <td><b>{ packagePreDispatch.company }</b></td>
                <td><b>{ packagePreDispatch.Reference_Number_1 }</b></td>
                <td>{ packagePreDispatch.Description_Return }</td>
                <td>{ packagePreDispatch.client }</td>
                <td>{ packagePreDispatch.Weight }</td>
                <td>{ packagePreDispatch.Width }</td>
                <td>{ packagePreDispatch.Length }</td>
                <td>{ packagePreDispatch.Height }</td>
                <td>{ packagePreDispatch.Route }</td>
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

    const modalPackageList = <React.Fragment>
                                    <div className="modal fade" id="modalPackageList" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog modal-lg">
                                            <div className="modal-content">
                                                <div className="modal-header">
                                                    <h5 className="modal-title text-primary" id="exampleModalLabel">
                                                        Package List Of The Pallet: <span className="text-success">{ PalletNumberForm }</span>
                                                        <p>COMPANY: <span className={ (statusPallet == 'Opened' ? 'text-success' : 'text-danger') }>{ companyViewPallet }</span></p>
                                                        <p>STATUS: <span className={ (statusPallet == 'Opened' ? 'text-success' : 'text-danger') }>{ statusPallet }</span></p>
                                                    </h5>
                                                    <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close" onClick={ () => handlerCloseModalPackage() }></button>
                                                </div>
                                                <div className="modal-body">
                                                    <div className="row" style={ {display: (filterDispatch == 'Closed' ? 'none' : 'block')} }>
                                                        <div className="col-lg-12 mb-2">
                                                            <form onSubmit={ (e) => handlerValidation(e) } autoComplete="off">
                                                                <div className="row">
                                                                    <div className="col-lg-3">
                                                                        <div className="form-group">
                                                                            <label htmlFor="" className="form">PACKAGE ID</label>
                                                                            <input id="Reference_Number_1" type="text" className="form-control" value={ Reference_Number_1 } onChange={ (e) => setNumberPackage(e.target.value) } maxLength="24" required readOnly={ readOnly }/>
                                                                        </div>
                                                                    </div>
                                                                    <div className="col-lg-9">
                                                                        <div className="form-group">
                                                                            <label htmlFor="" className="form">COMMENT</label>
                                                                            <input id="Description_Return" type="text" className="form-control" value={ Description_Return } onChange={ (e) => setDescription_Return(e.target.value) } maxLength="24" required readOnly={ readOnly }/>
                                                                        </div>
                                                                    </div>
                                                                    <div className="col-lg-12">
                                                                        <div className="form-group">
                                                                            <label htmlFor="" className="form">CLIENT</label>
                                                                            <input id="client" type="text" className="form-control" value={ client } onChange={ (e) => setClient(e.target.value) } maxLength="24" required readOnly={ readOnly }/>
                                                                        </div>
                                                                    </div>
                                                                    <div className="col-lg-3 mb-3">
                                                                        <div className="form-group">
                                                                            <label htmlFor="" className="form">WEIGHT</label>
                                                                            <input id="Weight" type="number" className="form-control" value={ Weight } step="0.01" min="0" max="150" onChange={ (e) => setWeight(e.target.value) } maxLength="24" required readOnly={ readOnly }/>
                                                                        </div>
                                                                    </div>
                                                                    <div className="col-lg-3 mb-3">
                                                                        <div className="form-group">
                                                                            <label htmlFor="" className="form">WIDTH</label>
                                                                            <input id="Width" type="number" className="form-control" value={ Width } step="0.01" min="0" max="150" onChange={ (e) => setWidth(e.target.value) } maxLength="24" required readOnly={ readOnly }/>
                                                                        </div>
                                                                    </div>
                                                                    <div className="col-lg-3 mb-3">
                                                                        <div className="form-group">
                                                                            <label htmlFor="" className="form">LENGTH</label>
                                                                            <input id="Length" type="number" className="form-control" value={ Length } step="0.01" min="0" max="150" onChange={ (e) => setLength(e.target.value) } maxLength="24" required readOnly={ readOnly }/>
                                                                        </div>
                                                                    </div>
                                                                    <div className="col-lg-3 mb-3">
                                                                        <div className="form-group">
                                                                            <label htmlFor="" className="form">HEIGHT</label>
                                                                            <input id="Height" type="number" className="form-control" value={ Height } step="0.01" min="0" max="150" onChange={ (e) => setHeight(e.target.value) } maxLength="24" required readOnly={ readOnly }/>
                                                                        </div>
                                                                    </div>
                                                                    <div className="col-lg-9"></div>
                                                                    <div className="col-lg-3">
                                                                        <div className="form-group">
                                                                            <button className="btn btn-primary form-control">Save</button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                    <div className="row">
                                                        <div className="col-lg-12 text-center mb-2">
                                                            {
                                                                typeMessageDispatch == 'success'
                                                                ?
                                                                    <h2 className="text-success">{ textMessage }</h2>
                                                                :
                                                                    ''
                                                            }

                                                            {
                                                                typeMessageDispatch == 'error'
                                                                ?
                                                                    <h2 className="text-danger">{ textMessage }</h2>
                                                                :
                                                                    ''
                                                            }

                                                            {
                                                                typeMessageDispatch == 'warning'
                                                                ?
                                                                    <h2 className="text-warning">{ textMessage }</h2>
                                                                :
                                                                    ''
                                                            }

                                                            {
                                                                textMessageDate != ''
                                                                ?
                                                                    <h2 className="text-warning">{ textMessageDate }</h2>
                                                                :
                                                                    ''
                                                            }
                                                        </div>
                                                        <div className="col-lg-12 form-group">
                                                            <audio id="soundPitidoSuccess" src="./sound/pitido-success.mp3" preload="auto"></audio>
                                                            <audio id="soundPitidoError" src="./sound/pitido-error.mp3" preload="auto"></audio>
                                                            <audio id="soundPitidoWarning" src="./sound/pitido-warning.mp3" preload="auto"></audio>
                                                            <audio id="soundPitidoBlocked" src="./sound/pitido-blocked.mp3" preload="auto"></audio>
                                                        </div>
                                                    </div>
                                                    <div className="row table-responsive">
                                                        <div className="col-lg-12">
                                                            <table className="table table-hover table-condensed">
                                                                <thead>
                                                                    <tr>
                                                                        <th>DATE</th>
                                                                        <th>HOUR</th>
                                                                        <th>COMPANY</th>
                                                                        <th>PACKAGE ID</th>
                                                                        <th>DESCRIPTION</th>
                                                                        <th>CLIENT</th>
                                                                        <th>WEIGHT</th>
                                                                        <th>WIDTH</th>
                                                                        <th>LENGTH</th>
                                                                        <th>HEIGHT</th>
                                                                        <th>ROUTE</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    { listPackageDispatchTable }
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="modal-footer" style={ {display: (filterDispatch == 'Closed' ? 'none' : 'block')} }>
                                                    <div className="row" style={ {width: '100%'} }>
                                                        <div className="col-lg-4">
                                                        </div>
                                                        <div className="col-lg-4">
                                                        </div>
                                                        <div className="col-lg-4">
                                                            <div className="form-group">
                                                                <label className="text-white">---</label>
                                                                <button type="button" className="btn btn-success form-control" onClick={ () => handlerClosePallete () }>
                                                                    RETURN COMPANY PALLET
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </React.Fragment>;

    const listAllTeam = () => {

        fetch(url_general +'team/listall')
        .then(res => res.json())
        .then((response) => {

            setListTeam(response.listTeam);
        });
    }

    const listAllDriverByTeam = (idTeam) => {

        if(idTeam)
        {
            setIdTeam(idTeam);
            setIdDriver(0);
            setListDriver([]);

            fetch(url_general +'driver/team/list/'+ idTeam)
            .then(res => res.json())
            .then((response) => {

                setListDriver(response.listDriver);
            });
        }
        else
        {
            setIdTeam(0);
            setListDriver([]);
        }
    }

    const [sendDispatch, setSendDispatch] = useState(1);

    const handlerValidation = (e) => {

        e.preventDefault();

        setTextMessage('');

        setReadOnly(true);
        setSendDispatch(0);

        const formData = new FormData();

        formData.append('Reference_Number_1', Reference_Number_1);
        formData.append('numberPallet', PalletNumberForm);
        formData.append('Description_Return', Description_Return);
        formData.append('client', client);
        formData.append('Weight', Weight);
        formData.append('Width', Width);
        formData.append('Length', Length);
        formData.append('Height', Height);

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch(url_general +'package-pre-rts/insert', {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json()).
        then((response) => {

                if(response.stateAction == 'packageInRts')
                {
                    setTextMessage('The package is in PRE RTS #'+ Reference_Number_1);
                    setTypeMessageDispatch('warning');
                    setNumberPackage('');

                    document.getElementById('soundPitidoWarning').play();
                }
                else if(response.stateAction == 'validatedLost')
                {
                    setTextMessage("THE PACKAGE WAS RECORDED BEFORE AS LOST #"+ Reference_Number_1);
                    setTypeMessageDispatch('warning');

                    document.getElementById('soundPitidoWarning').play();
                }
                else if(response.stateAction == 'packageReturnCompany')
                {
                    setTextMessage('The package is in RETURN COMPANY #'+ Reference_Number_1);
                    setTypeMessageDispatch('warning');
                    setNumberPackage('');

                    document.getElementById('soundPitidoWarning').play();
                }
                else if(response.stateAction == 'validatedFilterPackage')
                {
                    let packageBlocked  = response.packageBlocked;
                    let packageManifest = response.packageManifest;

                    if(packageBlocked)
                    {
                        Swal.fire({
                            icon: 'error',
                            title: 'PACKAGE BLOCKED #'+ Reference_Number_1,
                            text: packageBlocked.comment,
                            showConfirmButton: false,
                            timer: 2000,
                        });
                    }
                    //setTextMessage(" LABEL #"+ Reference_Number_1);

                    //setTextMessage(" LABEL #"+ Reference_Number_1);


                    setTypeMessage('primary');
                    setNumberPackage('');

                    document.getElementById('soundPitidoBlocked').play();
                }
                else if(response.stateAction == 'notCompnay')
                {
                    setTextMessage("THE PACKAGE BELONGS TO ANOTHER COMPANY #"+ Reference_Number_1);
                    setTypeMessageDispatch('warning');
                    setNumberPackage('');

                    document.getElementById('soundPitidoWarning').play();
                }
                else if(response.stateAction == 'notExists')
                {
                    setTextMessage("NO EXISTS IN [INBOUND, WAREHOUSE, DISPATCH, TERMINAL] #"+ Reference_Number_1);
                    setTypeMessageDispatch('error');
                    setNumberPackage('');

                    document.getElementById('soundPitidoError').play();
                }
                else if(response.stateAction == 'notValidatedRoute')
                {
                    setTextMessage("El paquete N° "+ Reference_Number_1 +" no corresponde a su ruta asignada!");
                    setTypeMessageDispatch('error');
                    setNumberPackage('');

                    document.getElementById('Reference_Number_1').focus();
                    document.getElementById('soundPitidoError').play();
                }
                else if(response.stateAction == 'returCompany')
                {
                    setTextMessage("The package N°"+ Reference_Number_1 +" was returned to the company!");
                    setTypeMessageDispatch('warning');
                    setNumberPackage('');

                    document.getElementById('soundPitidoWarning').play();
                }
                else if(response.stateAction == true)
                {
                    setTextMessage("SUCCESSFULLY PRE RTS #"+ Reference_Number_1);
                    setTextMessageDate('');
                    setTypeMessageDispatch('success');
                    
                    clearForm();

                    listPackagePreDispatch(PalletNumberForm);

                    document.getElementById('Reference_Number_1').focus();
                    document.getElementById('soundPitidoSuccess').play();
                }
                else
                {
                    setTextMessage("A problem has occurred, please try again");
                    setTypeMessageDispatch('error');
                    
                    document.getElementById('Reference_Number_1').focus();
                    document.getElementById('soundPitidoError').play();
                }

                setReadOnly(false);
                setSendDispatch(1);
            },
        );
    }

    const handlerImport = (e) => {

        e.preventDefault();

        if(idTeam != 0 || idDriver != 0)
        {
            const formData = new FormData();

            formData.append('idDriver', idDriver);
            formData.append('idTeam', idTeam);
            formData.append('file', file);

            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            LoadingShow();

            fetch(url_general +'package-dispatch/import', {
                headers: { "X-CSRF-TOKEN": token },
                method: 'post',
                body: formData
            })
            .then(res => res.json())
            .then((response) => {

                    if(response.stateAction)
                    {
                        swal("Se importó el archivo!", {

                            icon: "success",
                        });

                        document.getElementById('fileImport').value = '';

                        listAllPackageDispatch(1, StateSearch, RouteSearchList);

                        setViewButtonSave('none');
                    }

                    LoadingHide();
                },
            );
        }
        else
        {
            swal('Atención!', 'Debe seleccionar mínimo un Team para importar', 'warning');
        }
    }

    const listPackagePreDispatch = (palletNumber) => {

        fetch(url_general +'package-pre-rts/list/'+ palletNumber)
        .then(res => res.json())
        .then((response) => {

            setListPackage(response.packagePreRtsList);
            setFilterDispatch(response.palletRts.status);
        });
    }

    const handlerValidationPallet = (e) => {

        e.preventDefault();
    
        listPackagePreDispatch(PalletNumberForm);
        handlerOpenModalPackage();
    }

    const handlerViewPackage = (palletNumber, company, status) => {

        setTextMessage('');
        
        setCompanyViewPallet(company);
        setStatusPallet(status);
        setPalletNumberForm(palletNumber);
        listPackagePreDispatch(palletNumber);
        handlerOpenModalPackage();
    }

    const handlerPrintPallet = (palletNumber) => {

        window.open(url_general +'pallet-rts/print/'+ palletNumber);
    }

    const handlerOpenModalPackage = () => {

        let myModal = new bootstrap.Modal(document.getElementById('modalPackageList'), {

            keyboard: true
        });

        myModal.show();
    }

    const handlerCloseModalPackage = () => {

        let myModal = new bootstrap.Modal(document.getElementById('modalPackageList'), {

            keyboard: true
        });

        myModal.hide();
    }

    const palletListTable = palletList.map( (pallet, i) => {

        return (

            <tr key={i}>
                <td style={ { width: '100px'} }>
                    { pallet.created_at.substring(5, 7) }-{ pallet.created_at.substring(8, 10) }-{ pallet.created_at.substring(0, 4) }
                </td>
                <td>
                    { pallet.created_at.substring(11, 19) }
                </td>
                <td><b>{ pallet.number }</b></td>
                <td><b>{ pallet.company }</b></td>
                <td><b>{ pallet.quantityPackage }</b></td>
                <td>
                    {
                        (
                            pallet.status == 'Opened'
                            ?
                                <button className="alert alert-success font-weight-bold">{ pallet.status }</button>
                            :
                                <button className="alert alert-danger font-weight-bold">{ pallet.status }</button>
                        )
                    }
                </td>
                <td>
                    <button className="btn btn-success btn-sm mt-2" onClick={ () => handlerViewPackage(pallet.number, pallet.company, pallet.status) }>View package</button><br/>
                    <button className="btn btn-secondary btn-sm mt-2" onClick={ () => handlerPrintPallet(pallet.number) }>
                        <i className="bx bxs-printer"></i> View package
                    </button>
                </td>
            </tr>
        );
    });

    const clearForm = () => {

        setNumberPackage('');
        setDescription_Return('');
        setClient('');
        setWeight('');
        setWidth('');
        setLength('');
        setHeight('');
    }

    const clearValidation = () => {

        document.getElementById('returnReference_Number_1').style.display = 'none';
        document.getElementById('returnReference_Number_1').innerHTML     = '';

        document.getElementById('descriptionReturn').style.display = 'none';
        document.getElementById('descriptionReturn').innerHTML     = '';
    }

    const onBtnClickFile = () => {

        setViewButtonSave('none');

        inputFileRef.current.click();
    }

    const hanldlerCheckAll = () => {

        if(checkAll == 0)
        {
            var checkboxes = document.getElementsByName('checkDispatch');

            for(var i = 0; i < checkboxes.length ; i++)
            {
                checkboxes[i].checked = 1;
            }

            setCheckAll(1);
        }
        else
        {
            var checkboxes = document.getElementsByName('checkDispatch');

            for(var i = 0; i < checkboxes.length ; i++)
            {
                checkboxes[i].checked = 0;
            }

            setCheckAll(0);
        }
    }

    const handlerRedirectReturns = () => {

        location.href = 'package/return';
    }


    const listAllRole = () => {

        fetch(url_general +'role/list')
        .then(res => res.json())
        .then((response) => {

            setListRole(response.roleList);
        });
    }

    const handlerSaveTeam = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('idRole', idRole);
        formData.append('name', name);
        formData.append('nameOfOwner', nameOfOwner);
        formData.append('address', address);
        formData.append('phone', phone);
        formData.append('email', email);
        formData.append('idsRoutes', idsRoutes);
        formData.append('permissionDispatch', permissionDispatch);

        //clearValidationTeam();

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        LoadingShow();

        fetch(url_general +'team/insert', {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json()).
        then((response) => {

                if(response.stateAction)
                {
                    swal("Team was registered!", {

                        icon: "success",
                    });

                    listAllTeam();
                    clearFormTeam();
                }
                else(response.status == 422)
                {
                    for(const index in response.errors)
                    {
                        document.getElementById(index).style.display = 'block';
                        document.getElementById(index).innerHTML     = response.errors[index][0];
                    }
                }

                LoadingHide();
            },
        );
    }

    const listRoleSelect = listRole.map( (role, i) => {

        return (

            (
                role.name == 'Team'
                ?
                    <option value={ role.id }>{ role.name }</option>

                :
                    ''
            )
        );
    });

    const optionsCheckRoute = listRoute.map( (route, i) => {

        return (

            <div className="col-lg-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id={ 'idCheck'+ route.id } value={ route.id } onChange={ () => handleChange() }/>
                    <label class="form-check-label" for="gridCheck1">
                        { route.name }
                    </label>
                </div>
            </div>
        );
    });

    const handleChange = () => {

        let routesIds = '';

        listRoute.forEach( route => {

            if(document.getElementById('idCheck'+ route.id).checked)
            {
                routesIds = (routesIds == '' ? route.id : route.id +','+ routesIds);
            }
        });

        setIdsRoutes(routesIds);
    };

    const clearValidationTeam = () => {

        document.getElementById('idRole').style.display = 'none';
        document.getElementById('idRole').innerHTML     = '';

        document.getElementById('name').style.display = 'none';
        document.getElementById('name').innerHTML     = '';

        document.getElementById('nameOfOwner').style.display = 'none';
        document.getElementById('nameOfOwner').innerHTML     = '';

        document.getElementById('address').style.display = 'none';
        document.getElementById('address').innerHTML     = '';

        document.getElementById('phone').style.display = 'none';
        document.getElementById('phone').innerHTML     = '';

        document.getElementById('email').style.display = 'none';
        document.getElementById('email').innerHTML     = '';
    }

    const clearFormTeam = () => {

        setId(0);
        setIdRole(0);
        setName('');
        setNameOfOwner('');
        setAddress('');
        setPhone('');
        setEmail('');
    }

    const handlerOpenModalDriver = (id) => {

        listAllRole();
        clearFormTeam();

        let myModal = new bootstrap.Modal(document.getElementById('modalDriverInsert'), {

            keyboard: true
        });

        myModal.show();
    }

    const handlerSaveUser = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('idRole', idRole);
        formData.append('idTeam', idTeam);
        formData.append('name', name);
        formData.append('nameOfOwner', nameOfOwner);
        formData.append('address', address);
        formData.append('phone', phone);
        formData.append('email', email);

        //clearValidation();

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        LoadingShow();

        fetch(url_general +'driver/insert', {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json()).
        then((response) => {

                if(response.stateAction)
                {
                    swal("Driver was registered!", {

                        icon: "success",
                    });

                    listAllUser(1);
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

                LoadingHide();
            },
        );
    }

    const listRoleDriverSelect = listRole.map( (role, i) => {

        return (

            (
                role.name == 'Driver'
                ?
                    <option value={ role.id }>{ role.name }</option>
                :
                    ''
            )

        );
    });

    const [optionsCompanySearch, setOptionsCompanySearch] = useState([]);

    const listOptionCompany = (listCompanies) => {

        setOptionsCompanySearch([]);

        listCompanies.map( (company, i) => {

            optionsCompanySearch.push({ value: company.id, label: company.name });

            setOptionsCompanySearch(optionsCompanySearch);
        });
    }

    const [RouteSearch, setRouteSearch] = useState('');

    const handlerChangeRoute = (routes) => {

        if(routes.length != 0)
        {
            let routesSearch = '';

            routes.map( (route) => {

                routesSearch = routesSearch == '' ? route.value : routesSearch +','+ route.value;
            });

            setRouteSearch(routesSearch);

            //listAllPackageInbound(page, dataView, routesSearch, StateSearch);
        }
        else
        {
            //setRouteSearch('all');

            //listAllPackageInbound(page, dataView, 'all', StateSearch);
        }
    };

    const handlerChangeCompanyPallet = (routes) => {

        setCompanyPallet(routes.value);
    };

    const handlerChangeRouteList = (routes) => {

        if(routes.length != 0)
        {
            let routesSearch = '';

            routes.map( (route) => {

                routesSearch = routesSearch == '' ? route.value : routesSearch +','+ route.value;
            });

            setRouteSearchList(routesSearch);

            listAllPackageDispatch(1, StateSearch, routesSearch);
        }
        else
        {
            setRouteSearchList('all');

            listAllPackageDispatch(1, StateSearch, 'all');
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

    const handlerChangeState = (states) => {

        setPage(1);

        if(states.length != 0)
        {
            let statesSearch = '';

            states.map( (state) => {

                statesSearch = statesSearch == '' ? state.value : statesSearch +','+ state.value;
            });

            setStateSearch(statesSearch);

            listAllPackageDispatch(1, statesSearch, RouteSearchList);
        }
        else
        {
            setStateSearch('all');

            listAllPackageDispatch(1, 'all', RouteSearchList);
        }
    }

    return (

        <section className="section">
            { modalPackageList }
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row form-group">
                                    <div className="col-lg-12">
                                        <div className="row form-group">
                                            <div className="col-lg-2">
                                                <div className="form-group">
                                                    <button className="btn btn-success btn-sm form-control" onClick={  () => handlerExport() }>
                                                        <i className="ri-file-excel-fill"></i> EXPORT
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div className="row">
                                    <div className="col-lg-7 mb-3">
                                        <form onSubmit={ (e) => handlerValidationPallet(e) } autoComplete="off">
                                            <div className="form-group">
                                                <label htmlFor="">PALLET ID</label>
                                                <input id="PalletNumberForm" type="text" className="form-control" value={ PalletNumberForm } onChange={ (e) => setPalletNumberForm(e.target.value) } maxLength="30" required readOnly={ readOnlyPalet }/>
                                            </div>
                                        </form>
                                    </div>
                                    <div className="col-lg-3">
                                        <div className="form-group">
                                            <label htmlFor="">COMPANY:</label>
                                            <Select onChange={ (e) => handlerChangeCompanyPallet(e) } options={ optionsCompanySearch } />
                                        </div>
                                    </div>
                                    <div className="col-lg-2">
                                        <div className="form-group">
                                            <label htmlFor="" className="text-white">ROUTE:</label>
                                            <button className="btn btn-primary form-control" onClick={  () => handlerCreatePallet() }>CREATE PALLET</button>
                                        </div>
                                    </div>
                                </div>

                                <div className="row">
                                    <div className="col-lg-4 mb-3" style={ {paddingLeft: (isLoading ? '5%' : '')} }>
                                        {
                                            (
                                                isLoading
                                                ? 
                                                    <ReactLoading type="bubbles" color="#A8A8A8" height={20} width={50} />
                                                :
                                                    <b className="alert alert-success" style={ {borderRadius: '10px', padding: '10px'} }>PALLETS: { totalPackagePallet }</b>
                                            )
                                        }
                                    </div>
                                </div>
                                <div className="row">
                                    <div className="col-lg-2 mb-2">
                                        <div className="row">
                                            <div className="col-lg-12">
                                                <div className="form-group">
                                                    Start date:
                                                </div>
                                            </div>
                                            <div className="col-lg-12">
                                                <input type="date" className='form-control' value={ dateStart } onChange={ (e) => setDateStart(e.target.value) }/>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-lg-2 mb-2">
                                        <div className="row">
                                            <div className="col-lg-12">
                                                <div className="form-group">
                                                    End date :
                                                </div>
                                            </div>
                                            <div className="col-lg-12">
                                                <input type="date" className='form-control' value={ dateEnd } onChange={ (e) => setDateEnd(e.target.value) }/>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-lg-2 mb-2" style={ {display: 'none'} }>
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
                                    <div className="col-lg-2 mb-2" style={ {display: 'none'} }>
                                        <div className="row">
                                            <div className="col-lg-12">
                                                <div className="form-group">
                                                    States :
                                                </div>
                                            </div>
                                            <div className="col-lg-12">
                                                <Select isMulti onChange={ (e) => handlerChangeState(e) } options={ optionsStateSearch } />
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
                                                <th>PALET ID</th>
                                                <th>COMPANY</th>
                                                <th>QUANTITY</th>
                                                <th>STATUS</th>
                                                <th>ACTION</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            { palletListTable }
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div className="col-lg-12">
                                <Pagination
                                    activePage={page}
                                    totalItemsCount={ totalPackagePallet }
                                    itemsCountPerPage={ totalPagePallet }
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
        </section>
    );
}

export default PackageRts;

// DOM element
if (document.getElementById('packageRts')) {
    ReactDOM.render(<PackageRts />, document.getElementById('packageRts'));
}