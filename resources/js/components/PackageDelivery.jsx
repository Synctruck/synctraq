import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'

function PackageDelivery() {

    const [listPackageDelivery, setListPackageDelivery] = useState([]);
    const [listDeliveries, setListDeliveries]           = useState([]);

    const [quantityDelivery, setQuantityDelivery] = useState(0);

    const [file, setFile]             = useState('');
    const [btnDisplay, setbtnDisplay] = useState('none');

    const [page, setPage]                 = useState(1);
    const [totalPage, setTotalPage]       = useState(0);
    const [totalPackage, setTotalPackage] = useState(0);

    const [viewButtonSave, setViewButtonSave] = useState('none');

    const inputFileRef  = React.useRef();

    useEffect(() => {
 
        listAllPackage(page);
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

    const listAllPackage = (pageNumber) => {

        LoadingShow();

        fetch(url_general +'package-delivery/list/?page='+ pageNumber)
        .then(res => res.json())
        .then((response) => {

            setListPackageDelivery(response.packageListDelivery.data);
            setListDeliveries(response.listDeliveries);
            setTotalPackage(response.packageListDelivery.total);
            setTotalPage(response.packageListDelivery.per_page);
            setPage(response.packageListDelivery.current_page);
            setQuantityDelivery(response.quantityDelivery);

            LoadingHide();
        });
    }

    const handlerImport = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('file', file);

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        LoadingShow();

        fetch(url_general +'package-delivery/import', {
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

                    listAllPackage();
                    setbtnDisplay('none');
                }

                LoadingHide();
            },
        );
    }

    const handlerChangePage = (pageNumber) => {

        listAllPackage(pageNumber);
    }

    const listPackageDeliveryTable = listPackageDelivery.map( (packageDelivery, i) => {

        let idsImages = packageDelivery.photoUrl.split(',');
        let imgs      = '';
        let urlImage  = '';

        if(idsImages.length == 1)
        {
            imgs = <img src={ 'https://d15p8tr8p0vffz.cloudfront.net/'+ idsImages[1] +'/800x.png' } width="100"/>;

            urlImage = 'https://d15p8tr8p0vffz.cloudfront.net/'+ idsImages[1] +'/800x.png';
        }
        else if(idsImages.length >= 2)
        {
            imgs =  <>
                        <img src={ 'https://d15p8tr8p0vffz.cloudfront.net/'+ idsImages[0] +'/800x.png' } width="50" style={ {border: '2px solid red'} }/>
                        <img src={ 'https://d15p8tr8p0vffz.cloudfront.net/'+ idsImages[1] +'/800x.png' } width="50" style={ {border: '2px solid red'} }/>
                    </>

            urlImage = 'https://d15p8tr8p0vffz.cloudfront.net/'+ idsImages[0] +'/800x.png' + 'https://d15p8tr8p0vffz.cloudfront.net/'+ idsImages[1] +'/800x.png'
        }

        return (

            <tr key={i}>
                <td style={ { width: '100px'} }>
                    { packageDelivery.created_at.substring(5, 7) }-{ packageDelivery.created_at.substring(8, 10) }-{ packageDelivery.created_at.substring(0, 4) }
                </td>
                <td>
                    { packageDelivery.created_at.substring(11, 19) }
                </td>
                <td>{ team }</td>
                <td>{ driver }</td>
                <td><b>{ packageDelivery.Reference_Number_1 }</b></td>
                <td>{ packageDelivery.Dropoff_Contact_Name }</td>
                <td>{ packageDelivery.Dropoff_Contact_Phone_Number }</td>
                <td>{ packageDelivery.Dropoff_Address_Line_1 }</td>
                <td>{ packageDelivery.Dropoff_City }</td>
                <td>{ packageDelivery.Dropoff_Province }</td>
                <td>{ packageDelivery.Dropoff_Postal_Code }</td>
                <td>{ packageDelivery.Weight }</td>
                <td>{ packageDelivery.Route }</td>
                <td>
                    <img src={ urlImage } width="100"/>
                </td>
            </tr>
        );
    });

    const onBtnClickFile = () => {

        setViewButtonSave('none');
        
        inputFileRef.current.click();
    }

    return (

        <section className="section">
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row form-group">
                                    <div className="col-lg-10"> 
                                        LIST OF PACKAGES IN DELIVERY
                                    </div>
                                    <div className="col-lg-2">
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
                                <div className="row">
                                    <div className="col-lg-2">
                                        <b className="alert-warning" style={ {borderRadius: '10px', padding: '10px'} }>Delivery: { quantityDelivery }</b> 
                                    </div>
                                </div>
                            </h5>
                            <div className="row form-group table-responsive">
                                <div className="col-lg-12">
                                    <table className="table table-hover table-condensed">
                                        <thead>
                                            <tr>
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
                                                <th>IMAGE</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            { listPackageDeliveryTable }
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

export default PackageDelivery;

// DOM element
if (document.getElementById('delivery')) {
    ReactDOM.render(<PackageDelivery />, document.getElementById('delivery'));
}