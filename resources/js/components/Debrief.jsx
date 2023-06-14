import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import ReactLoading from 'react-loading';

function Debrief() {

    const [disabledButton, setDisabledButton] = useState(false);
    const [titleModal, setTitleModal]         = useState('');
    const [textSearch, setSearch]             = useState('');
    const [textButtonSave, setTextButtonSave] = useState('Save');
    const [isLoading, setIsLoading]           = useState(false);

    const [listDriver, setListDriver]   = useState([]);
    const [packageList, setPackageList] = useState([]);

    useEffect(() => {

        listAllDriver();

    }, [textSearch])

    const listAllDriver = () => {

        setIsLoading(true);

        fetch(url_general +'driver/defrief/list')
        .then(res => res.json())
        .then((response) => {

            setListDriver(response.driverList);
            setIsLoading(false);
        });
    }

    const handlerOpenModal = (id) => {

        clearValidation();

        if(id)
        {
            setTitleModal('Update Driver')
            setTextButtonSave('Update');
        }
        else
        {
            listAllRole();
            clearForm();
            setTitleModal('Add Driver');
            setTextButtonSave('Save');
        }

        let myModal = new bootstrap.Modal(document.getElementById('modalDriverInsert'), {

            keyboard: true
        });

        myModal.show();
    }

    const getPackages = (id) => {

        LoadingShow();

        fetch(url_general +'driver/defrief/list-packages/'+ id)
        .then(response => response.json())
        .then(response => {

            setPackageList(response.listPackages);
            setTitleModal('PACKAGE LIST');

            let myModal = new bootstrap.Modal(document.getElementById('modalPackages'), {

                keyboard: true
            });

            myModal.show();

            LoadingHide();
        });
    }

    const listDriverTable = listDriver.map( (user, i) => {

        return (

            <tr key={i}>
                <td>{ i + 1 }</td>
                <td>{ user['fullName'] }</td>
                <td>{ user['email'] }</td>
                <td>{ user['quantityOfPackages'] }</td>
                <td>
                    <button className="btn btn-primary btn-sm" title="Edit" onClick={ () => getPackages(user['idDriver']) }>
                        View Packages
                    </button>
                </td>
            </tr>
        );
    });

    const handlerChangeStatus = (newStatus, Reference_Number_1) => {

        alert(newStatus);
    }

    const packageListTable = packageList.map( (packageDispatch, i) => {

        return (

            <tr key={i}>
                <td>{ i + 1 }</td>
                <td>{ packageDispatch.Reference_Number_1 }</td>
                <td>
                    <select name="" id="" className="form-control" onChange={ (e) => handlerChangeStatus(e.target.value, packageDispatch.Reference_Number_1) }>
                        <option value="all">Select</option>
                        <option value="Lost">Lost</option>
                        <option value="Warehouse">Warehouse</option>
                    </select>
                </td>
            </tr>
        );
    });

    const handlerSaveUser = () => {

    };

    const modalPackages = <React.Fragment>
                                    <div className="modal fade" id="modalPackages" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog">
                                            <form onSubmit={ handlerSaveUser }>
                                                <div className="modal-content">
                                                    <div className="modal-header">
                                                        <h5 className="modal-title text-primary" id="exampleModalLabel">{ titleModal }</h5>
                                                        <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div className="modal-body">
                                                        <div className="row table-responsive">
                                                            <div className="col-lg-12">
                                                                <table className="table table-hover table-condensed">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>#</th>
                                                                            <th>PACKAGE ID</th>
                                                                            <th>ACTION</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        { packageListTable }
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div className="modal-footer">
                                                        <button type="button" className="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </React.Fragment>;

    return (

        <section className="section">
            { modalPackages }
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <div className="row">
                                <div className="col-lg-12 mb-2">
                                    <input type="text" value={textSearch} onChange={ (e) => setSearch(e.target.value) } className="form-control" placeholder="Buscar..."/>
                                </div>
                                <div className="col-lg-12 mb-2" style={ {padding: (isLoading ? '1%' : '')} }>
                                    {
                                        (
                                            isLoading
                                            ? 
                                                <ReactLoading type="bubbles" color="#A8A8A8" height={20} width={50} />
                                            :
                                                ''
                                        )
                                    }
                                </div>
                            </div>
                            <div className="row form-group">
                                <div className="col-lg-12">
                                    <table className="table table-hover table-condensed">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>DRIVER FULL NAME</th>
                                                <th>EMAIL</th>
                                                <th>PACKAGES QUANTITY</th>
                                                <th>ACTIONS</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            { listDriverTable }
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="col-lg-12">
                </div>
            </div>
        </section>
    );
}

export default Debrief;

// DOM element
if (document.getElementById('debrief')) {
    ReactDOM.render(<Debrief />, document.getElementById('debrief'));
}
