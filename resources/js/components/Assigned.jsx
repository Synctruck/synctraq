import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'

function Assigned() {

    const [listAssigned, setListPackageAssigned] = useState([]);
    const [listTeam, setListTeam]                = useState([]);
    const [listDriver, setListDriver]            = useState([]);
    const [roleUser, setRoleUser]                = useState([]);
    const [listRoute, setListRoute]              = useState([]);
    const [listState , setListState]             = useState([]);

    const [checkAll, setCheckAll] = useState(0);
    const [readOnly, setReadOnly] = useState(false);

    const [quantityAssigned, setQuantityAssigned] = useState(0);

    const [dataView, setDataView] = useState('today');

    const [Reference_Number_1, setNumberPackage] = useState('');
    const [idTeam, setIdTeam] = useState(0);
    const [idDriver, setIdDriver] = useState(0);
    const [idDriverAsing, setIdDriverAsing] = useState(0);
    const [StateSearch, setStateSearch] = useState('');

    const [textMessage, setTextMessage] = useState('');
    const [typeMessageDispatch, setTypeMessageDispatch] = useState('');

    const [typeMessage, setTypeMessage] = useState('');

    const [file, setFile]             = useState('');

    const [page, setPage]                 = useState(1);
    const [totalPage, setTotalPage]       = useState(0);
    const [totalPackage, setTotalPackage] = useState(0);

    const inputFileRef  = React.useRef();

    const [viewButtonSave, setViewButtonSave] = useState('none');

    useEffect(() => {

        listAllTeam();

        document.getElementById('Reference_Number_1').focus();

    }, [idTeam]);

    useEffect(() => {

        setPage(1);

        listAllAssigned(1, dataView);

    }, [dataView]);

    /*useEffect(() => {

        if(String(file) == 'undefined' || file == '')
        {
            setViewButtonSave('none');
        }
        else
        {
            setViewButtonSave('block');
        }

    }, [file]);*/

    const listAllAssigned = (pageNumber, dataView) => {

        fetch(url_general +'assigned/list/'+ dataView +'/'+ idTeam +'/?page='+ pageNumber)
        .then(res => res.json())
        .then((response) => {

            setListPackageAssigned(response.assignedList.data);
            setTotalPackage(response.assignedList.total);
            setTotalPage(response.assignedList.per_page);
            setPage(response.assignedList.current_page);
            setQuantityAssigned(response.quantityAssigned)
            setRoleUser(response.roleUser);
            setListState(response.listState);

            if(listState.length == 0)
            {
                listOptionState(response.listState);
            }
        });
    }

    const handlerChangePage = (pageNumber) => {

        listAllAssigned(pageNumber, dataView);
    }

    const listAllRoute = (pageNumber) => {

        setListRoute([]);

        fetch(url_general +'routes/list')
        .then(res => res.json())
        .then((response) => {

            setListRoute(response.routeList.data);
        });
    }

    const [Reference_Number_1_Edit, setReference_Number_1] = useState('');
    const [Dropoff_Contact_Name, setDropoff_Contact_Name] = useState('');
    const [Dropoff_Contact_Phone_Number, setDropoff_Contact_Phone_Number] = useState('');
    const [Dropoff_Address_Line_1, setDropoff_Address_Line_1] = useState('');
    const [Dropoff_Address_Line_2, setDropoff_Address_Line_2] = useState('');
    const [Dropoff_City, setDropoff_City] = useState('');
    const [Dropoff_Province, setDropoff_Province] = useState('');
    const [Dropoff_Postal_Code, setDropoff_Postal_Code] = useState('');
    const [Weight, setWeight] = useState('');
    const [Route, setRoute] = useState('');

    const [readOnlyInput, setReadOnlyInput]   = useState(false);
    const [disabledButton, setDisabledButton] = useState(false);

    const [textButtonSave, setTextButtonSave] = useState('Guardar');

    const optionsRole = listRoute.map( (route, i) => {

        return (

            <option key={ i } value={ route.name } selected={ Route == route.name ? true : false }> {route.name}</option>
        );
    });

    const handlerOpenModalEditPackage = (PACKAGE_ID) => {

        fetch(url_general +'package-dispatch/get/'+ PACKAGE_ID)
        .then(res => res.json())
        .then((response) => {

            setReference_Number_1(PACKAGE_ID);
            setDropoff_Contact_Name(response.package.Dropoff_Contact_Name);
            setDropoff_Contact_Phone_Number(response.package.Dropoff_Contact_Phone_Number);
            setDropoff_Address_Line_1(response.package.Dropoff_Address_Line_1);
            setDropoff_Address_Line_2((response.package.Dropoff_Address_Line_2 ? response.package.Dropoff_Address_Line_2 : ''));
            setDropoff_City(response.package.Dropoff_City);
            setDropoff_Province(response.package.Dropoff_Province);
            setDropoff_Postal_Code(response.package.Dropoff_Postal_Code);
            setWeight(response.package.Weight);
            setRoute(response.package.Route);
        });

        //clearValidation();

        setReadOnlyInput(true);

        let myModal = new bootstrap.Modal(document.getElementById('modalPackageEdit'), {

            keyboard: true
        });

        myModal.show();
    }

    const handlerUpdatePackage = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('Reference_Number_1', Reference_Number_1_Edit);
        formData.append('Dropoff_Contact_Name', Dropoff_Contact_Name);
        formData.append('Dropoff_Contact_Phone_Number', Dropoff_Contact_Phone_Number);
        formData.append('Dropoff_Address_Line_1', Dropoff_Address_Line_1);
        formData.append('Dropoff_Address_Line_2', Dropoff_Address_Line_2);
        formData.append('Dropoff_City', Dropoff_City);
        formData.append('Dropoff_Province', Dropoff_Province);
        formData.append('Dropoff_Postal_Code', Dropoff_Postal_Code);
        formData.append('Weight', Weight);
        formData.append('Route', Route);
        formData.append('status', true);

        clearValidationEdit();

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        setDisabledButton(true);
        setTextButtonSave('Loading...');

        let url = 'package-dispatch/update'

        fetch(url_general + url, {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json()).
        then((response) => {

                setTextButtonSave('Guardar');
                setDisabledButton(false);

                if(response.stateAction)
                {
                    swal('Se actualizó el Package!', {

                        icon: "success",
                    });

                    listAllAssigned(page, dataView);
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

    const clearValidationEdit = () => {

        document.getElementById('Reference_Number_1_Edit').style.display = 'none';
        document.getElementById('Reference_Number_1_Edit').innerHTML     = '';

        document.getElementById('Dropoff_Contact_Name').style.display = 'none';
        document.getElementById('Dropoff_Contact_Name').innerHTML     = '';

        document.getElementById('Dropoff_Contact_Phone_Number').style.display = 'none';
        document.getElementById('Dropoff_Contact_Phone_Number').innerHTML     = '';

        document.getElementById('Dropoff_Address_Line_1').style.display = 'none';
        document.getElementById('Dropoff_Address_Line_1').innerHTML     = '';

        document.getElementById('Dropoff_City').style.display = 'none';
        document.getElementById('Dropoff_City').innerHTML     = '';

        document.getElementById('Dropoff_Province').style.display = 'none';
        document.getElementById('Dropoff_Province').innerHTML     = '';

        document.getElementById('Dropoff_Postal_Code').style.display = 'none';
        document.getElementById('Dropoff_Postal_Code').innerHTML     = '';

        document.getElementById('Weight').style.display = 'none';
        document.getElementById('Weight').innerHTML     = '';

        document.getElementById('Route').style.display = 'none';
        document.getElementById('Route').innerHTML     = '';
    }

    const modalPackageEdit = <React.Fragment>
                                    <div className="modal fade" id="modalPackageEdit" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog">
                                            <form onSubmit={ handlerUpdatePackage }>
                                                <div className="modal-content">
                                                    <div className="modal-header">
                                                        <h5 className="modal-title text-primary" id="exampleModalLabel">Update Package</h5>
                                                        <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div className="modal-body">
                                                        <div className="row">
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>PACKAGE ID</label>
                                                                    <div id="Reference_Number_1_Edit" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ Reference_Number_1_Edit } className="form-control" onChange={ (e) => setReference_Number_1(e.target.value) } maxLength="15" readOnly={ readOnlyInput } required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>CLIENT</label>
                                                                    <div id="Dropoff_Contact_Name" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ Dropoff_Contact_Name } className="form-control" onChange={ (e) => setDropoff_Contact_Name(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div className="row">
                                                            <div className="col-lg-12">
                                                                <div className="form-group">
                                                                    <label>CONTACT</label>
                                                                    <div id="Dropoff_Contact_Phone_Number" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ Dropoff_Contact_Phone_Number } className="form-control" onChange={ (e) => setDropoff_Contact_Phone_Number(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>ADDRESS 1</label>
                                                                    <div id="Dropoff_Address_Line_1" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ Dropoff_Address_Line_1 } className="form-control" onChange={ (e) => setDropoff_Address_Line_1(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>ADDRESS 2</label>
                                                                    <div id="Dropoff_Address_Line_1" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ Dropoff_Address_Line_2 } className="form-control" onChange={ (e) => setDropoff_Address_Line_2(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div className="row">
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>CITY</label>
                                                                    <div id="Dropoff_City" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ Dropoff_City } className="form-control" onChange={ (e) => setDropoff_City(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>STATE</label>
                                                                    <div id="Dropoff_Province" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ Dropoff_Province } className="form-control" onChange={ (e) => setDropoff_Province(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div className="row">
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>ZIP C</label>
                                                                    <div id="Dropoff_Postal_Code" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ Dropoff_Postal_Code } className="form-control" onChange={ (e) => setDropoff_Postal_Code(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>WEIGHT</label>
                                                                    <div id="Weight" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ Weight } className="form-control" onChange={ (e) => setWeight(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div className="row">
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>ROUTE</label>
                                                                    <div id="Route" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <select name="" id="" className="form-control" onChange={ (e) => setRoute(e.target.value) } required>
                                                                        <option value="" style={ {display: 'none'} }>Seleccione una ruta</option>
                                                                        { optionsRole }
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div className="modal-footer">
                                                        <button type="button" className="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                        <button className="btn btn-primary" disabled={ disabledButton }>Actualizar</button>
                                                    </div>
                                                </div>
                                            </form>
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

    const handlerValidation = (e) => {

        e.preventDefault();

        if(listTeamStates.length > 0)
        {
            const formData = new FormData();

            formData.append('Reference_Number_1', Reference_Number_1);
            formData.append('idTeam', idTeam);
            formData.append('idDriver', idDriver);
            formData.append('states', StateSearch);
            formData.append('listTeamStates', listTeamStates);

            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            setReadOnly(true);

            fetch(url_general +'assigned/insert', {
                headers: { "X-CSRF-TOKEN": token },
                method: 'post',
                body: formData
            })
            .then(res => res.json())
            .then((response) => {

                    if(response.stateAction == 'notState')
                    {
                        setTextMessage("DOES NOT CORRESPOND TO THE VALIDATION STATE #"+ Reference_Number_1);
                        setTypeMessageDispatch('warning');
                        setNumberPackage('');

                        document.getElementById('soundPitidoError').play();
                    }
                    else if(response.stateAction == 'notRoute')
                    {
                        setTextMessage("DOS NOT CORRESPOND TO THE TEAM ROUTE #"+ Reference_Number_1);
                        setTypeMessageDispatch('warning');
                        setNumberPackage('');

                        document.getElementById('soundPitidoError').play();
                    }
                    else if(response.stateAction == 'notInbound')
                    {
                        setTextMessage("NOT VALIDATED INBOUND #"+ Reference_Number_1);
                        setTypeMessageDispatch('warning');
                        setNumberPackage('');

                        document.getElementById('soundPitidoError').play();
                    }
                    else if(response.stateAction == 'notInland')
                    {
                        setTextMessage("NOT INLAND o 67660 #"+ Reference_Number_1);
                        setTypeMessageDispatch('warning');
                        setNumberPackage('');

                        document.getElementById('soundPitidoError').play();
                    }
                    else if(response.stateAction == 'notExists')
                    {
                        setTextMessage("NO EXISTS #"+ Reference_Number_1);
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
                    else if(response.stateAction == 'validated')
                    {
                        setTextMessage("DUPLICATED #"+ Reference_Number_1);
                        setTypeMessageDispatch('warning');
                        setNumberPackage('');
                        document.getElementById('soundPitidoWarning').play();
                    }
                    else if(response.stateAction == 'packageExist')
                    {
                        setTextMessage("El paquete N° "+ Reference_Number_1 +" existe, pero no pasó la validación Inbound!");
                        setTypeMessageDispatch('warning');
                        setNumberPackage('');

                        document.getElementById('soundPitidoError').play();
                    }
                    else if(response.stateAction == 'delivery')
                    {
                        setTextMessage("PACKAGE WAS MARKED AS DELIVERED #"+ Reference_Number_1);
                        setTypeMessageDispatch('warning');
                        setNumberPackage('');

                        document.getElementById('soundPitidoError').play();
                    }
                    else if(response.stateAction == 'dispatch')
                    {
                        setTextMessage("PACKAGE STATUS DISPATCH #"+ Reference_Number_1);
                        setTypeMessageDispatch('warning');
                        setNumberPackage('');

                        document.getElementById('soundPitidoWarning').play();
                    }
                    else if(response.stateAction)
                    {
                        setTextMessage("SUCCESSFULLY ASSIGNED #"+ Reference_Number_1);
                        setTypeMessageDispatch('success');
                        setNumberPackage('');

                        listAllAssigned(1, dataView);

                        document.getElementById('Reference_Number_1').focus();
                        document.getElementById('soundPitidoSuccess').play();
                    }
                    else
                    {
                        setTextMessage("El paquete N° "+ Reference_Number_1 +" no existe!");
                        setTypeMessageDispatch('error');
                        setNumberPackage('');

                        document.getElementById('Reference_Number_1').focus();
                        document.getElementById('soundPitidoError').play();
                    }

                    setReadOnly(false);
                },
            );
        }
        else
        {
            swal('Atención', 'You must select at least 1 state to make the assignment', 'warning');
        }
    }

    const handlerImport = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('file', file);

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        LoadingShow();

        fetch(url_general +'package-dispatch/import', {
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

                    document.getElementById('fileImport').value = '';

                    listAllAssigned(1, dataView);

                    setViewButtonSave('none');
                }

                LoadingHide();
            },
        );
    }

    const changeReference = (Reference_Number) => {

        if(idDriverAsing == 0)
        {
            swal('Attention!', 'You must select a Driver to assign the package', 'warning');

            return 0;
        }

        setNumberPackage(Reference_Number);

        let formData = new FormData();

        formData.append('Reference_Number_1', Reference_Number);
        formData.append('idTeam', idTeam);
        formData.append('idDriver', idDriverAsing);

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        swal({
            title: "Are you sure?",
            text: "The package will be assigned to the Driver!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {

            if(willDelete)
            {
                let url = 'package-dispatch/change'

                fetch(url_general + url, {
                    headers: { "X-CSRF-TOKEN": token },
                    method: 'post',
                    body: formData
                })
                .then(res => res.json()).
                then((response) => {

                        setTextButtonSave('Guardar');
                        setDisabledButton(false);

                        setTextMessage('RE-ASSIGN PACKAGE #'+ Reference_Number);
                        setTypeMessageDispatch('warning');
                        setNumberPackage('');

                        listAllAssigned(1, dataView);

                        document.getElementById('Reference_Number_1').focus();
                        document.getElementById('soundPitidoSuccess').play();
                    },
                );
            }
        });
    }

    const listAssignedTable = listAssigned.map( (assigned, i) => {

        return (

            <tr key={i} className="alert-success">
                <td style={ {display: 'none'} }>
                    <input class="form-check-input" type="checkbox" id={ 'idCheck'+ assigned.Reference_Number_1 } name="checkDispatch" value={ assigned.Reference_Number_1 }/>
                </td>
                <td>{(quantityAssigned - i) - (20 * (page - 1))}</td>
                <td style={ { width: '100px'} }>
                    { assigned.assignedDate ? assigned.assignedDate.substring(5, 7) +'-'+ assigned.assignedDate.substring(8, 10) +'-'+ assigned.assignedDate.substring(0, 4) : '' }
                </td>
                <td>
                    { assigned.assignedDate ? assigned.assignedDate.substring(11, 19) : '' }
                </td>
                <td>{ assigned.team.name }</td>
                <td>{ assigned.driver ? assigned.driver.name +' '+ assigned.driver.nameOfOwner : '' }</td>
                <td><b>{ assigned.Reference_Number_1 }</b></td>
                <td>{ assigned.Dropoff_Contact_Name }</td>
                <td>{ assigned.Dropoff_Contact_Phone_Number }</td>
                <td>{ assigned.Dropoff_Address_Line_1 }</td>
                <td>{ assigned.Dropoff_City }</td>
                <td>{ assigned.Dropoff_Province }</td>
                <td>{ assigned.Dropoff_Postal_Code }</td>
                <td>{ assigned.Weight }</td>
                <td>{ assigned.Route }</td>
                <td style={ {textAlign: 'center', display: 'none'} }>
                    { idUserGeneral == assigned.idUserDispatch && roleUser == 'Team' ? <><button className="btn btn-success btn-sm" value={ assigned.Reference_Number_1 } onClick={ (e) => changeReference(assigned.Reference_Number_1) }>Asignar</button><br/><br/></> : '' }
                    <button className="btn btn-primary btn-sm" onClick={ () => handlerOpenModalEditPackage(assigned.Reference_Number_1) }>
                        <i className="bx bx-edit-alt"></i>
                    </button>
                </td>
            </tr>
        );
    });

    const [listTeamStates, setListTeamStates] = useState([]);

    const listTeamSelect = listTeam.map( (team, i) => {

        let addTeam = true;

        if(idTeam)
        {
            listTeamStates.forEach( (teamState) => {

                if(teamState.split('=>')[0] == team.name)
                {
                    addTeam = false;
                }
            });
        }

        if(addTeam)
        {
            return (

                <option value={ team.id +'=>'+ team.name }>{ team.name }</option>
            );
        }
    });

    const listDriverSelect = listDriver.map( (driver, i) => {

        return (

            <option value={ driver.id }>{ driver.name +' '+ driver.nameOfOwner }</option>
        );
    });

    const handlerReturn = (idPackage) => {

        const formData = new FormData();

        formData.append('Reference_Number_1', idPackage);

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        LoadingShow();

        fetch(url_general +'package/return/dispatch', {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json()).
        then((response) => {

                if(response.stateAction == 'notUser')
                {
                    setTextMessage("The package was validated by another driver #"+ idPackage);
                    setTypeMessage('error');
                    setReturnNumberPackage('');

                    document.getElementById('return_Reference_Number_1').focus();
                    document.getElementById('soundPitidoError').play();
                }
                else if(response.stateAction == 'notDispatch')
                {
                    setTextMessage("The package was not validated as Dispatch #"+ idPackage);
                    setTypeMessage('warning');
                    setNumberPackage('');
                }
                else if(response.stateAction)
                {
                    setTextMessage("Package was returned #"+ idPackage);
                    setTypeMessage('success');
                    setNumberPackage('');

                    document.getElementById('return_Reference_Number_1').focus();
                    document.getElementById('soundPitidoSuccess').play();

                    listAllAssigned();
                }
                else
                {
                    setTextMessage("There was a problem, please try the same action again.");
                    setTypeMessage('error');
                    setNumberPackage('');

                    document.getElementById('return_Reference_Number_1').focus();
                    document.getElementById('soundPitidoError').play();
                }

                LoadingHide();
            },
        );
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

        location.href = url_general +'package/download/onfleet/'+ idTeam +'/'+ idDriver +'/'+ type +'/'+ valuesCheck;
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

        location.href = url_general +'package/download/roadwarrior/'+ idTeam +'/'+ idDriver +'/'+ type +'/'+ valuesCheck;
    }

    const clearForm = () => {

        setReturnNumberPackage('');
        setDescriptionReturn('');
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

    const [optionsStateSearch, setOptionsStateSearch] = useState([]);
    const [defaultValue, setDefaultValue] = useState('default');

    const listOptionState = (listState) => {

        setOptionsStateSearch([]);

        listState.map( (state, i) => {

            optionsStateSearch.push({ value: state.Dropoff_Province, label: state.Dropoff_Province });

            setOptionsStateSearch(optionsStateSearch);
        });
    }

    const handlerChangeState = (states) => {

        if(states.length != 0)
        {
            let statesSearch = '';

            states.map( (state) => {

                statesSearch = statesSearch == '' ? state.value : statesSearch +';'+ state.value;
            });

            setStateSearch(statesSearch);
        }
    };

    const listTeamStatesViews = listTeamStates.map( (teamState, i) =>{

        return  <tr>
                    <td>
                        { teamState.split('=>')[0] }
                    </td>
                    <td>
                        { teamState.split('=>')[1] }
                    </td>
                </tr>
    });

    const handlerAddTeamState = () => {

        if(idTeam != 0 && StateSearch != '')
        {
            listTeamStates.push( idTeam.split('=>')[1] +'=>'+ StateSearch);

            setListTeamStates(listTeamStates);
            listAllTeam();

            setTimeout( () => {

                setStateSearch('');

            }, 500);
        }
        else
        {
            swal('Atención', 'Debe seleccionar 1 Team y mínimo 1 o más States para asignar', 'warning');
        }
    }

    return (

        <section className="section">
            { modalPackageEdit }
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row form-group">

                                    <div className="col-lg-12 form-group">
                                        <div className="row form-group">
                                            <div className="col-lg-4">
                                                Validation For Virtual Office
                                            </div>
                                            <div className="col-lg-8 text-center">
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
                                            </div>
                                            {
                                                roleUser == 'Master'
                                                ?
                                                    <div className="col-lg-2" style={ {display: 'none'} }>
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
                                                :
                                                    ''
                                            }

                                        </div>
                                        <div className="row form-group">
                                            <div className="col-lg-9">
                                                <form onSubmit={ handlerValidation } autoComplete="off">
                                                    <div className="row">
                                                        <div className="col-lg-8">
                                                            <div className="form-group">
                                                                <label htmlFor="">PACKAGE ID</label>
                                                                <input id="Reference_Number_1" type="text" className="form-control" value={ Reference_Number_1 } onChange={ (e) => setNumberPackage(e.target.value) } maxLength="15" required readOnly={ readOnly }/>
                                                            </div>
                                                        </div>

                                                        <div className="col-lg-4">
                                                            <div className="form-group">
                                                                <label htmlFor="">TEAM</label>
                                                                <select name="" id="" className="form-control" onChange={ (e) => setIdTeam(e.target.value) }>
                                                                    <option value="">All</option>
                                                                    { listTeamSelect }
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div className="col-lg-2 form-group">
                                                        <audio id="soundPitidoSuccess" src="./sound/pitido-success.mp3" preload="auto"></audio>
                                                        <audio id="soundPitidoError" src="./sound/pitido-error.mp3" preload="auto"></audio>
                                                        <audio id="soundPitidoWarning" src="./sound/pitido-warning.mp3" preload="auto"></audio>
                                                    </div>
                                                </form>
                                            </div>
                                            <div className="col-lg-3">
                                                <div className="row">
                                                    <div className="col-lg-12">
                                                            State :
                                                    </div>
                                                    <div className="col-lg-12">
                                                        <Select id="selectStates" isMulti onChange={ (e) => handlerChangeState(e) } options={ optionsStateSearch }/>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="row form-group">
                                            <div className="col-lg-9"></div>
                                            <div className="col-lg-3 text-right">
                                                <button className="btn btn-primary" onClick={ () => handlerAddTeamState() }>Add</button>
                                            </div>
                                        </div>
                                        <div className="row">
                                            <div className="col-lg-6"></div>
                                            <div className="col-lg-6">
                                                <table className="table table-hover table-condensed table-bordered">
                                                    <tr>
                                                        <th>Teams</th>
                                                        <th>States</th>
                                                    </tr>
                                                    { listTeamStatesViews }
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div className="row">
                                    <div className="col-lg-8">
                                        <div className="form-group">
                                            <b className="alert-success" style={ {borderRadius: '10px', padding: '10px'} }>Assigned: { quantityAssigned }</b>
                                        </div>
                                    </div>
                                    <div className="col-lg-4">
                                        <div className="row">
                                            <div className="col-lg-3">
                                                <div className="form-group">
                                                    View :
                                                </div>
                                            </div>
                                            <div className="col-lg-9">
                                                <select className="form-control" onChange={ (e) => setDataView(e.target.value) }>
                                                    <option value="all">All</option>
                                                    <option value="today" selected>Today</option>
                                                </select>
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
                                                <th style={ {display: 'none'} }>
                                                    <input class="form-check-input" type="checkbox" id="checkAllPackage" value="1" onChange={ () => hanldlerCheckAll() }/>
                                                </th>
                                                <th>#</th>
                                                <th>DATE</th>
                                                <th>HOUR</th>
                                                <th><b>TEAM</b></th>
                                                <th><b>DRIVER</b></th>
                                                <th>PACKAGE ID</th>
                                                <th>CLIENT</th>
                                                <th>CONTACT</th>
                                                <th>ADDREESS</th>
                                                <th>CITY</th>
                                                <th>STATE</th>
                                                <th>ZIP CODE</th>
                                                <th>WEIGHT</th>
                                                <th>ROUTE</th>
                                                <th style={ {display: 'none'} }>ACTION</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            { listAssignedTable }
                                        </tbody>
                                    </table>
                                </div>
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
        </section>
    );
}

export default Assigned;

// DOM element
if (document.getElementById('assigned')) {
    ReactDOM.render(<Assigned />, document.getElementById('assigned'));
}
