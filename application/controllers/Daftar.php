<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Daftar extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->library('form_validation');
	}

	public function index()
	{
		$data['jur']=$this->db->get('jurusan')->result();
		$this->load->view('form_daftar', $data);
	}
	public function post()
	{
		$this->form_validation->set_rules('gol_drh','Gol_drh','required|max_length[2]');
		$data=$this->input->post();
		// no_pendaftaran
		$this->db->select('*');
		$this->db->from('pendaftaran');
		$this->db->order_by('created_at','desc');
		$last=$this->db->get()->row_array();
		// printf($last['no_daftar']);
		// die();
		if ($last!=null) {
			$getLast=substr(strval($last['no_daftar']), -3);
			$lastId=intval($getLast)+1;
		}else{
			$lastId=1;
		};
		$data['jur']=$this->db->get('jurusan')->result();	
		$data['created_at']=date('Y-m-d h:m:i');
		$taActive=$this->db->get_where('tahun',['status'=>'y'])->row_array()['tahun'];
		if ($taActive==null) {
			echo 'Belum ada tahun aktif !';
			die();
		};
		$data['ta']=$taActive;
		$year=substr( date('Y'), -2);
		$month=substr( date('m'), -2);
		$jurkode=$this->db->get_where('jurusan',['id_jur'=>$data['pil']])->row_array()['sym'];
		$data['no_daftar']=$jurkode.$year.$month.str_pad($lastId, 3, '0', STR_PAD_LEFT);
		// end of no pendaftaran
		$config['upload_path']          = './uploads/foto/';
		$config['file_name']            = 'mhs_'.date('YmdHis').'_'.uniqid();
		$config['allowed_types']        = 'jpg';
		$config['max_size']             = 1024;
		// echo "<pre>";
		// print_r($data);
		// print_r($_FILES);
		// echo "</pre>";
		// die();
		$this->load->library('upload', $config);
		if ($this->form_validation->run()) {
			if ( ! $this->upload->do_upload('photo')){
				$this->session->set_flashdata('input', $data);
				$this->session->set_flashdata('error', $this->upload->display_errors());
				$this->load->view('form_daftar', $data);
				// redirect(site_url('welcome', $data));
			}else{
				unset($data['jur']);
				$data['photo'] = 'foto/'.$this->upload->data("file_name");
				$data['nm_lkp']=strtoupper($data['nm_lkp']);
				$data['tmp_lhr']=strtoupper($data['tmp_lhr']);
				$data['alm_lkp']=strtoupper($data['alm_lkp']);
				$data['gol_drh']=strtoupper($data['gol_drh']);
				$data['wrg_ngr']=strtoupper($data['wrg_ngr']);
				$data['nm_ortu']=strtoupper($data['nm_ortu']);
				$data['pkrj_ortu']=strtoupper($data['pkrj_ortu']);
				$data['nm_skl']=strtoupper($data['nm_skl']);
				$data['alm_skl']=strtoupper($data['alm_skl']);
				$data['jrs_skl']=strtoupper($data['jrs_skl']);
				$this->db->insert('pendaftaran', $data);
				redirect(site_url('daftar/success/'.$data['no_daftar']));
			}
		}else{
			$this->session->set_flashdata('error', validation_errors());
			$this->load->view('form_daftar', $data);
		}
	}
	public function cek()
	{
		$data['jur']=$this->db->get('jurusan')->result();
		if ($this->session->userdata()) {
			$data['calon']=$this->db->get_where('pendaftaran',['id_dftr'=>$this->input->post('src')])->row_array();
			$this->load->view('success2', $data);
			// echo $this->input->post('src');
		}else{
			$data['calon']=$this->db->get_where('pendaftaran',['no_daftar'=>$this->input->post('src')])->row_array();
			$this->load->view('success', $data);
		}
	}
	public function success($no_daftar)
	{
		$data['calon']=$this->db->get_where('pendaftaran',['no_daftar'=>$no_daftar])->row_array();
		$data['jur']=$this->db->get('jurusan')->result();
		$this->load->view('success', $data);
	}
	public function cetak($no_daftar)
	{
		$this->generateQr($no_daftar);
		$this->load->library('pdf');
		$data['calon']=$this->db->get_where('pendaftaran',['no_daftar'=>$no_daftar])->row_array();
		$data['jur']=$this->db->get('jurusan')->result();
		// $this->load->view('CetakPendaftaran',$data);
		$this->pdf->setPaper('A4', 'potrait');
		$this->pdf->filename = "BuktiDaftar_".$no_daftar.".pdf";
		$this->pdf->load_view('CetakPendaftaran', $data);

	}
	public function generateQr($no_daftar)
	{
		$this->load->library('ciqrcode'); //pemanggilan library QR CODE

        $config['cacheable']    = true; //boolean, the default is true
        $config['cachedir']     = './assets/'; //string, the default is application/cache/
        $config['errorlog']     = './assets/'; //string, the default is application/logs/
        $config['imagedir']     = './assets/qr/'; //direktori penyimpanan qr code
        $config['quality']      = true; //boolean, the default is true
        $config['size']         = '1024'; //interger, the default is 1024
        $config['black']        = array(224,255,255); // array, default is array(255,255,255)
        $config['white']        = array(70,130,180); // array, default is array(0,0,0)
        $this->ciqrcode->initialize($config);

        $image_name=$no_daftar.'.png'; //buat name dari qr code sesuai dengan nim

        $params['data'] = $no_daftar; //data yang akan di jadikan QR CODE
        $params['level'] = 'H'; //H=High
        $params['size'] = 10;
        $params['savename'] = FCPATH.$config['imagedir'].$image_name; //simpan image QR CODE ke folder assets/images/
        $this->ciqrcode->generate($params); // fungsi untuk generate QR CODE
    }
}