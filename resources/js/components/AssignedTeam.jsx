import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'

function AssignedTeam() {

    const [listAssigned, setListPackageAssigned] = useState([]);
    const [listTeam, setListTeam]                       = useState([]);
    const [listDriver, setListDriver]                   = useState([]);
    const [roleUser, setRoleUser]                       = useState([]);
    const [listRoute, setListRoute]                     = useState([]);

    const [checkAll, setCheckAll] = useState(0);
    const [readOnly, setReadOnly] = useState(false);

    const [quantityAssigned, setQuantityAssigned] = useState(0);

    const [dataView, setDataView] = useState('today');

    const [Reference_Number_1, setNumberPackage] = useState('');
    const [idTeam, setIdTeam] = useState(0);
    const [idDriver, setIdDriver] = useState(0);
    const [idDriverAsing, setIdDriverAsing] = useState(0);

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

        listAllDriverByTeam(idUserGeneral);

        document.getElementById('Reference_Number_1').focus();

    }, []);

    useEffect(() => {

        setPage(1);

        listAllAssigned(1, dataView);

    }, [dataView]);

    const listAllAssigned = (pageNumber, dataView) => {

        fetch(url_general +'assignedTeam/list/'+ dataView +'/'+ idUserGeneral +'/?page='+ pageNumber)
        .then(res => res.json())
        .then((response) => {

            setListPackageAssigned(response.assignedList.data);
            setTotalPackage(response.assignedList.total);
            setTotalPage(response.assignedList.per_page);
            setPage(response.assignedList.current_page);
            setQuantityAssigned(response.quantityAssigned)
            setRoleUser(response.roleUser);
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

    const listAllDriverByTeam = (idTeam) => {

        setIdTeam(idTeam);
        setListDriver([]);
        
        fetch(url_general +'driver/team/list/'+ idTeam)
        .then(res => res.json())
        .then((response) => {

            setListDriver(response.listDriver);
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

    const listAllTeam = () => {

        fetch(url_general +'team/listall')
        .then(res => res.json())
        .then((response) => {

            setListTeam(response.listTeam);
        });
    }

    const handlerValidation = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('Reference_Number_1', Reference_Number_1);
        formData.append('idDriver', idDriver);

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        setReadOnly(true);

        fetch(url_general +'assignedTeam/insert', {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json()).
        then((response) => {

                if(response.stateAction == 'notAssigned')
                {
                    setTextMessage("THE PACKAGE WAS NOT ASSIGNED TO YOU #"+ Reference_Number_1);
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
                    swal("Se importó el archivo!", {

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
            swal('Atención!', 'Debe seleccionar un Driver para asignar el paquete', 'warning');

            return 0;
        }

        setNumberPackage(Reference_Number);

        let formData = new FormData();

        formData.append('Reference_Number_1', Reference_Number);
        formData.append('idTeam', idTeam);
        formData.append('idDriver', idDriverAsing);

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        swal({
            title: "Esta seguro?",
            text: "Se asignará el paquete al Driver!",
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
                <td>{(page - 1) * 20 + (i + 1)}</td>
                <td style={ { width: '100px'} }>
                    { assigned.assignedDate ? assigned.assignedDate.substring(0, 10) : '' }
                </td>
                <td>
                    { assigned.assignedDate ? assigned.assignedDate.substring(11, 19) : '' }
                </td>
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
                    setTextMessage("El paquete N° "+ idPackage +" fue validado por otro Driver!");
                    setTypeMessage('error');
                    setReturnNumberPackage('');

                    document.getElementById('return_Reference_Number_1').focus();
                    document.getElementById('soundPitidoError').play();
                }
                else if(response.stateAction == 'notDispatch')
                {
                    setTextMessage("El paquete N° "+ idPackage +" no fue validado como Dispatch!");
                    setTypeMessage('warning');
                    setNumberPackage('');
                }
                else if(response.stateAction)
                {
                    setTextMessage("Paquete N° "+ idPackage +" fue retornado!");
                    setTypeMessage('success');
                    setNumberPackage('');
                    
                    document.getElementById('return_Reference_Number_1').focus();
                    document.getElementById('soundPitidoSuccess').play();

                    listAllAssigned();
                }
                else
                {
                    setTextMessage("Hubo un problema, intente nuevamente realizar la misma acción.");
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
                                            <div className="col-lg-4">
                                                Validation For Driver Assignation
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
                                        </div>
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
                                                        <label htmlFor="">DRIVERS</label>
                                                        <select name="" id="" className="form-control" onChange={ (e) => setIdDriver(e.target.value) } required>
                                                            <option value="">All</option>
                                                            { listDriverSelect }
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
                                </div>
                                <div className="row">
                                    <div className="col-lg-8">
                                        <div className="form-group">
                                            <b className="alert-success" style={ {borderRadius: '10px', padding: '10px'} }>Assigned: { quantityAssigned }</b> 
                                        </div>
                                    </div>
                                    <div className="col-lg-4" style={ {display: 'none'}}>
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
                                                <th>#</th>
                                                <th>DATE</th>
                                                <th>HOUR</th>
                                                <th>DRIVER</th>
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

export default AssignedTeam;

// DOM element
if (document.getElementById('assignedTeam')) {
    ReactDOM.render(<AssignedTeam />, document.getElementById('assignedTeam'));
}