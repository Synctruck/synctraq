import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'

function PackageNotExists() {

    const [listPackageNotExists, setListPackageNotExists] = useState([]);
    const [listRoute, setListRoute] = useState([]);

    const [titleModal, setTitleModal] = useState('');

    const [textButtonSave, setTextButtonSave] = useState('Guardar');

    const [roleUser, setRoleUser] = useState([]);

    useEffect(() => {

        listAllPackageReturn();
        listAllRoute();

    }, []);

    const listAllPackageReturn = () => {

        fetch(url_general +'package-not-exists/list')
        .then(res => res.json())
        .then((response) => {

            setListPackageNotExists(response.packageListNotExists);
            setRoleUser(response.roleUser);
        });
    }

    const listAllRoute = (pageNumber) => {

        setListRoute([]);

        fetch(url_general +'routes/list')
        .then(res => res.json())
        .then((response) => {

            setListRoute(response.routeList.data);
        });
    }

    const handlerOpenModal = (Reference_Number_1) => {

        clearValidation();

        clearForm();
        setTitleModal('Agregar Package');
        setTextButtonSave('Guardar');

        setReference_Number_1(Reference_Number_1);

        let myModal = new bootstrap.Modal(document.getElementById('modalPackageInsert'), {

            keyboard: true
        });

        myModal.show();
    }

    const clearValidation = () => {

        document.getElementById('Reference_Number_1').style.display = 'none';
        document.getElementById('Reference_Number_1').innerHTML     = '';

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

    const [Reference_Number_1, setReference_Number_1] = useState('');
    const [Dropoff_Contact_Name, setDropoff_Contact_Name] = useState('');
    const [Dropoff_Contact_Phone_Number, setDropoff_Contact_Phone_Number] = useState('');
    const [Dropoff_Address_Line_1, setDropoff_Address_Line_1] = useState('');
    const [Dropoff_City, setDropoff_City] = useState('');
    const [Dropoff_Province, setDropoff_Province] = useState('');
    const [Dropoff_Postal_Code, setDropoff_Postal_Code] = useState('');
    const [Weight, setWeight] = useState('');
    const [Route, setRoute] = useState(0);

    const optionsRole = listRoute.map( (route, i) => {

        return (

            <option key={ i } value={ route.name }> {route.name}</option>
        );
    });
    
    const handlerSavePackage = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('Reference_Number_1', Reference_Number_1);
        formData.append('Dropoff_Contact_Name', Dropoff_Contact_Name);
        formData.append('Dropoff_Contact_Phone_Number', Dropoff_Contact_Phone_Number);
        formData.append('Dropoff_Address_Line_1', Dropoff_Address_Line_1);
        formData.append('Dropoff_City', Dropoff_City);
        formData.append('Dropoff_Province', Dropoff_Province);
        formData.append('Dropoff_Postal_Code', Dropoff_Postal_Code);
        formData.append('Weight', Weight);
        formData.append('Route', Route);

        clearValidation();

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            LoadingShow();

            fetch(url_general +'package-manifest/insert', {
                headers: { "X-CSRF-TOKEN": token },
                method: 'post',
                body: formData
            })
            .then(res => res.json()).
            then((response) => {

                    if(response.stateAction)
                    {
                        swal("Se registró el Package!", {

                            icon: "success",
                        });

                        clearForm();
                        listAllPackageReturn();
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

    const modalPackageInsert = <React.Fragment>
                                    <div className="modal fade" id="modalPackageInsert" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog">
                                            <form onSubmit={ handlerSavePackage }>
                                                <div className="modal-content">
                                                    <div className="modal-header">
                                                        <h5 className="modal-title text-primary" id="exampleModalLabel">{ titleModal }</h5>
                                                        <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div className="modal-body">
                                                        <div className="row">
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>PACKAGE ID</label>
                                                                    <div id="Reference_Number_1" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ Reference_Number_1 } className="form-control" onChange={ (e) => setReference_Number_1(e.target.value) } required readOnly/>
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
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>CONTACT</label>
                                                                    <div id="Dropoff_Contact_Phone_Number" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ Dropoff_Contact_Phone_Number } className="form-control" onChange={ (e) => setDropoff_Contact_Phone_Number(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>ADDRESS</label>
                                                                    <div id="Dropoff_Address_Line_1" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ Dropoff_Address_Line_1 } className="form-control" onChange={ (e) => setDropoff_Address_Line_1(e.target.value) } required/>
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
                                                        <button className="btn btn-primary">{ textButtonSave }</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </React.Fragment>;
    
    const clearForm = () => {

        setReference_Number_1('');
        setDropoff_Contact_Name('');
        setDropoff_Contact_Phone_Number('');
        setDropoff_Address_Line_1('');
        setDropoff_City('');
        setDropoff_Province('');
        setDropoff_Postal_Code('');
        setWeight('');
        setRoute(0);
    }

    const handlerExport = () => {

        location.href = url_general +'package-not-exists/export-excel';
    }

    const listPackageNotExistsTable = listPackageNotExists.map( (packageNotExists, i) => {

        return (

            <tr key={i}>

                <td style={ { width: '100px'} }>
                    { packageNotExists.Date_Inbound ? packageNotExists.Date_Inbound.substring(5, 7) +'-'+ packageNotExists.Date_Inbound.substring(8, 10) +'-'+ packageNotExists.Date_Inbound.substring(0, 4) : '' }
                </td>
                <td style={ { width: '80px'} }>
                    { packageNotExists.Date_Inbound ? packageNotExists.Date_Inbound.substring(11, 19) : '' }
                </td>
                <td><b>{ packageNotExists.Reference_Number_1 }</b></td>
                <td>
                    <button className="btn btn-success btn-sm" onClick={ () => handlerOpenModal(packageNotExists.Reference_Number_1) }>
                        <i className="bx bxs-plus-square"></i> Agregar
                    </button>
                </td>
            </tr>
        );
    });

    return (

        <section className="section">
            { modalPackageInsert }
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row">
                                    <div className="col-lg-10">
                                        <div className="form-group">
                                            List of Non-Existing Packages <br/>
                                        </div>
                                    </div>
                                    <div className="col-lg-2">
                                        <div className="form-group">
                                            <button className="btn btn-success btn-sm form-control" onClick={ handlerExport }><i className="ri-file-excel-fill"></i> Export</button>
                                        </div>
                                    </div>
                                </div>
                                <div className="row">
                                    <div className="col-lg-12">
                                        <div className="form-group">
                                            <b className="alert-success" style={ {borderRadius: '10px', padding: '10px'} }>Non Existing: { listPackageNotExists.length }</b> 
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
                                                <th>PACKAGE ID</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            { listPackageNotExistsTable }
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

export default PackageNotExists;

// DOM element
if (document.getElementById('packageNotExists')) {
    ReactDOM.render(<PackageNotExists />, document.getElementById('packageNotExists'));
}