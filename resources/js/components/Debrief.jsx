import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import ReactLoading from 'react-loading';

function Debrief() {

    const [idDriver, setIdDriver]             = useState(0);
    const [disabledButton, setDisabledButton] = useState(false);
    const [titleModal, setTitleModal]         = useState('');
    const [textSearch, setSearch]             = useState('');
    const [textButtonSave, setTextButtonSave] = useState('Save');
    const [isLoading, setIsLoading]           = useState(false);

    const [listTeam, setListTeam]             = useState([]);
    const [listDriver, setListDriver]         = useState([]);
    const [packageListAux, setPackageListAux] = useState([]);
    const [packageList, setPackageList]       = useState([]);
    const [searchPackage, setSearchPackage]   = useState('');
    const [idTeam, setIdTeam]                 = useState(0);

    useEffect(() => {

        listAllDriver();
        listAllTeam();

    }, [idTeam])

    const listAllDriver = () => {

        setIsLoading(true);

        fetch(url_general +'driver/defrief/list/'+ idTeam)
        .then(res => res.json())
        .then((response) => {

            setListDriver(response.driverList);
            setIsLoading(false);
        });
    }

    const handlerOpenModal = (id) => {

        getPackages(id);

        let myModal = new bootstrap.Modal(document.getElementById('modalPackagesListDebrief'), {

            keyboard: true
        });

        myModal.show();
    }

    const getPackages = (id) => {

        LoadingShowMap();

        setIdDriver(id);
        setPackageList([]);
        setPackageListAux([]);

        fetch(url_general +'driver/defrief/list-packages/'+ id)
        .then(response => response.json())
        .then(response => {

            setPackageList(response.listPackages);
            setPackageListAux(response.listPackages);
            setTitleModal('PACKAGE LIST: '+ response.listPackages.length);

            LoadingHideMap();
        });
    }

    const listDriverTable = listDriver.map( (user, i) => {

        return (

            <tr key={i}>
                <td><b>{ user['team'] }</b></td>
                <td>{ user['fullName'] }</td>
                <td>{ user['email'] }</td>
                <td>{ user['quantityOfPackages'] }</td>
                <td>
                    <button className="btn btn-primary btn-sm" title="Edit" onClick={ () => handlerOpenModal(user['idDriver']) }>
                        View Packages
                    </button>
                </td>
            </tr>
        );
    }); 

    const handlerChangeStatus = (newStatus, Reference_Number_1) => {
        
        document.getElementById('commentNMI'+ Reference_Number_1).style.display = 'none';

        if(newStatus == 'Delivery')
        {
            window.open(url_general +'report/delivery?Reference_Number='+ Reference_Number_1);
        }
        else if(newStatus == 'NMI')
        {
            document.getElementById('commentNMI'+ Reference_Number_1).style.display = 'block';
        }
        else
        {
            let comment = prompt("Enter the comment");

            if(comment != '' && comment != null)
            {
                hanldlerSaveNewStatus(newStatus, Reference_Number_1, comment)
            }
            else
            {
                swal('Attention', 'You have not entered anything. Select the status again', 'warning');
            }
        }
    }

    const handlerChangeComment = (newStatus, Reference_Number_1, comment) => {

        hanldlerSaveNewStatus(newStatus, Reference_Number_1, comment)
    }

    const hanldlerSaveNewStatus = (newStatus, Reference_Number_1, comment) => {

        LoadingShowMap();

        fetch(url_general +'driver/defrief/packages-change-status/'+ Reference_Number_1 +'/'+ newStatus +'/'+ comment)
        .then(response => response.json())
        .then(response => {

            if(response.statusAction == true)
            {
                swal('Correct', 'The package was moved to the selected status', 'success');

                setSearchPackage('');

                getPackages(idDriver);
            }
            else if(response.statusAction == 'packageNotExists')
            {
                swal('Attention', 'Package does not exist in DISPATCH or DELETE', 'warning');
            }

            LoadingHideMap();
        });
    }

    const handlerOpenHistory = (Reference_Number_1) => {

        document.getElementById('searchPackage').value = Reference_Number_1;

        SearchPackageReferenceId();

        document.getElementById('btnCloseModalPackagesListDebrief').click();
        document.getElementById('btnReturnToDebrief').style.display = 'block';
    }

    const packageListTable = packageList.map( (packageDispatch, i) => {

        return (

            <tr key={i}>
                <td>{ i + 1 }</td>
                <td style={ { width: '100px'} }>
                    <b>{ packageDispatch.created_at.substring(5, 7) }-{ packageDispatch.created_at.substring(8, 10) }-{ packageDispatch.created_at.substring(0, 4) }</b><br/>
                    { packageDispatch.created_at.substring(11, 19) }
                </td>
                <td><a href="#" onClick={ (e) => handlerOpenHistory(packageDispatch.Reference_Number_1) }>{ packageDispatch.Reference_Number_1 }</a></td>
                <td>{ packageDispatch.status }</td>
                <td>{ packageDispatch.lateDays }</td>
                <td>
                    <select name="" id="" className="form-control mb-1" onChange={ (e) => handlerChangeStatus(e.target.value, packageDispatch.Reference_Number_1) }>
                        <option value="all">Select</option>
                        <option value="Delivery">DELIVERY</option>
                        <option value="Lost">LOST</option>
                        <option value="NMI">NMI</option>
                        <option value="Warehouse">WAREHOUSE</option>
                    </select>
                </td>
                <td>
                    <select id={ 'commentNMI'+ packageDispatch.Reference_Number_1 } className="form-control" style={ {display: 'none'} } onChange={ (e) => handlerChangeComment('NMI', packageDispatch.Reference_Number_1, e.target.value) }>
                        <option value="all">Select Comment</option>
                        <option value="Address not found">Address not found</option>
                        <option value="Customer unavailable">Customer unavailable</option>
                        <option value="Need access code">Need access code</option>
                        <option value="Customer refused">Customer refused</option>
                    </select>
                </td>
            </tr>
        );
    });

    const handlerSearchPackageId = (e) => {

        e.preventDefault();

        if(searchPackage == '')
        {
            setPackageList(packageListAux);
        }
        else
        {
            let packageListNow = packageList.filter(element => element.Reference_Number_1 == searchPackage);

            setPackageList(packageListNow);
        }
    };

    const modalPackages = <React.Fragment>
                                    <div className="modal fade" id="modalPackagesListDebrief" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog modal-md">
                                            <div className="modal-content">
                                                <div className="modal-header">
                                                    <h5 className="modal-title text-primary" id="exampleModalLabel">{ titleModal }</h5>
                                                    <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div className="modal-body">
                                                    <div className="row table-responsive">
                                                        <form onSubmit={ handlerSearchPackageId }>
                                                            <div className="col-lg-12">
                                                                <div className="form-group">
                                                                    <input type="text" className="form-control" value={ searchPackage } onChange={ (e) => setSearchPackage(e.target.value) } placeholder="Search PACKAGE ID"/>
                                                                </div>
                                                            </div>
                                                        </form>
                                                        <div className="col-lg-12">
                                                            <table className="table table-hover table-condensed">
                                                                <thead>
                                                                    <tr>
                                                                        <th>#</th>
                                                                        <th>DATE</th>
                                                                        <th>PACKAGE ID</th>
                                                                        <th>STATUS</th>
                                                                        <th>LATE DAYS</th>
                                                                        <th>ACTION</th>
                                                                        <th></th>
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
                                                    <button type="button" id="btnCloseModalPackagesListDebrief" className="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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

    const listTeamSelect = listTeam.map( (team, i) => {

        return (

            <option value={ team.id } className={ (team.useXcelerator == 1 ? 'text-warning' : '') }>{ team.name }</option>
        );
    })

    return (

        <section className="section">
            { modalPackages }
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <div className="row">
                                <div className="col-lg-3">
                                    <div className="form-group">
                                        <label className="form">TEAM</label>
                                        <select name="" id="" className="form-control" onChange={ (e) => setIdTeam(e.target.value) } required>
                                            <option value="0">All</option>
                                            { listTeamSelect }
                                        </select>
                                    </div>
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
                                                <th>TEAM</th>
                                                <th>DRIVER</th>
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
