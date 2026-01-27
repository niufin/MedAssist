package `in`.niufin.doctor

import android.Manifest
import android.app.Activity
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.graphics.Bitmap
import android.net.Uri
import android.os.Bundle
import android.os.Environment
import android.view.View
import android.webkit.JavascriptInterface
import android.webkit.JsResult
import android.webkit.ValueCallback
import android.webkit.WebChromeClient
import android.webkit.WebResourceRequest
import android.webkit.WebSettings
import android.webkit.WebView
import android.webkit.WebViewClient
import android.webkit.WebResourceError
import android.widget.Button
import android.widget.LinearLayout
import android.widget.ProgressBar
import android.widget.Toast
import androidx.activity.OnBackPressedCallback
import androidx.activity.result.ActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.core.app.ActivityCompat
import androidx.core.content.ContextCompat
import androidx.core.content.FileProvider
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout
import `in`.niufin.doctor.BuildConfig
import java.io.File
import java.io.IOException
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

class MainActivity : AppCompatActivity() {
    private lateinit var webView: WebView
    private lateinit var progressBar: ProgressBar
    private lateinit var swipeRefreshLayout: SwipeRefreshLayout
    private lateinit var errorLayout: LinearLayout
    private lateinit var btnRetry: Button
    private var filePathCallback: ValueCallback<Array<Uri>>? = null
    private var isCameraFlowPendingPermission: Boolean = false
    private val capturedCameraUris: MutableList<Uri> = mutableListOf()
    private var pendingCameraUri: Uri? = null

    private val takePictureLauncher = registerForActivityResult(
        ActivityResultContracts.TakePicture()
    ) { success: Boolean ->
        val callback = filePathCallback
        if (callback == null) {
            resetCameraCaptureState()
            return@registerForActivityResult
        }

        val uri = pendingCameraUri
        pendingCameraUri = null

        if (success && uri != null) {
            capturedCameraUris.add(uri)
            showCameraContinueDialog()
        } else {
            if (capturedCameraUris.isNotEmpty()) {
                callback.onReceiveValue(capturedCameraUris.toTypedArray())
            } else {
                callback.onReceiveValue(null)
            }
            resetFileChooserState()
        }
    }
    private val fileChooserLauncher = registerForActivityResult(
        ActivityResultContracts.StartActivityForResult()
    ) { result: ActivityResult ->
        val callback = filePathCallback
        if (callback == null) {
            resetCameraCaptureState()
            return@registerForActivityResult
        }

        var results: Array<Uri>? = null

        if (result.resultCode == Activity.RESULT_OK) {
            val data = result.data
            if (data != null) {
                val clipData = data.clipData
                if (clipData != null) {
                    val uris = mutableListOf<Uri>()
                    for (i in 0 until clipData.itemCount) {
                        val item = clipData.getItemAt(i)
                        uris.add(item.uri)
                    }
                    results = uris.toTypedArray()
                } else {
                    data.data?.let {
                        results = arrayOf(it)
                    }
                }
            }
        }

        callback.onReceiveValue(results)
        resetFileChooserState()
    }

    companion object {
        private const val CAMERA_PERMISSION_REQUEST_CODE = 1002
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)

        swipeRefreshLayout = findViewById<SwipeRefreshLayout>(R.id.swipeRefresh)
        webView = findViewById(R.id.webview)
        progressBar = findViewById(R.id.progressBar)
        errorLayout = findViewById(R.id.errorLayout)
        btnRetry = findViewById(R.id.btnRetry)

        btnRetry.setOnClickListener {
            errorLayout.visibility = View.GONE
            swipeRefreshLayout.visibility = View.VISIBLE
            webView.reload()
        }
        
        val webSettings: WebSettings = webView.settings
        webSettings.javaScriptEnabled = true
        webSettings.domStorageEnabled = true
        webSettings.loadWithOverviewMode = true
        webSettings.useWideViewPort = true
        webSettings.builtInZoomControls = true
        webSettings.displayZoomControls = false

        webView.addJavascriptInterface(WebAppInterface(this), "Android")

        onBackPressedDispatcher.addCallback(this, object : OnBackPressedCallback(true) {
            override fun handleOnBackPressed() {
                if (webView.canGoBack()) {
                    webView.goBack()
                } else {
                    finish()
                }
            }
        })

        webView.webViewClient = object : WebViewClient() {
            override fun shouldOverrideUrlLoading(view: WebView?, request: WebResourceRequest?): Boolean {
                val url = request?.url.toString()
                if (url.contains("doctor.niufin.cloud")) {
                    return false
                }
                val intent = Intent(Intent.ACTION_VIEW, Uri.parse(url))
                startActivity(intent)
                return true
            }

            @Deprecated("Deprecated in WebViewClient")
            override fun shouldOverrideUrlLoading(view: WebView?, url: String?): Boolean {
                if (url != null && url.contains("doctor.niufin.cloud")) {
                    return false
                }
                val intent = Intent(Intent.ACTION_VIEW, Uri.parse(url))
                startActivity(intent)
                return true
            }

            override fun onPageStarted(view: WebView?, url: String?, favicon: Bitmap?) {
                super.onPageStarted(view, url, favicon)
                progressBar.visibility = View.VISIBLE
                errorLayout.visibility = View.GONE
                swipeRefreshLayout.visibility = View.VISIBLE
            }

            override fun onReceivedError(view: WebView?, request: WebResourceRequest?, error: WebResourceError?) {
                if (request?.isForMainFrame == true) {
                    swipeRefreshLayout.visibility = View.GONE
                    errorLayout.visibility = View.VISIBLE
                }
            }

            @Deprecated("Deprecated in WebViewClient")
            override fun onReceivedError(view: WebView?, errorCode: Int, description: String?, failingUrl: String?) {
                swipeRefreshLayout.visibility = View.GONE
                errorLayout.visibility = View.VISIBLE
            }

            override fun onPageFinished(view: WebView?, url: String?) {
                super.onPageFinished(view, url)
                progressBar.visibility = View.GONE
                swipeRefreshLayout.isRefreshing = false

                if (url != null) {
                    val path = Uri.parse(url).path
                    swipeRefreshLayout.isEnabled = path != "/dashboard"
                }
            }
        }

        webView.webChromeClient = object : WebChromeClient() {
            override fun onJsConfirm(view: WebView?, url: String?, message: String?, result: JsResult?): Boolean {
                AlertDialog.Builder(this@MainActivity)
                    .setTitle("Confirmation")
                    .setMessage(message)
                    .setPositiveButton("OK") { _, _ -> result?.confirm() }
                    .setNegativeButton("Cancel") { _, _ -> result?.cancel() }
                    .setCancelable(false)
                    .show()
                return true
            }

            override fun onJsAlert(view: WebView?, url: String?, message: String?, result: JsResult?): Boolean {
                AlertDialog.Builder(this@MainActivity)
                    .setTitle("Alert")
                    .setMessage(message)
                    .setPositiveButton("OK") { _, _ -> result?.confirm() }
                    .setCancelable(false)
                    .show()
                return true
            }

            override fun onShowFileChooser(
                view: WebView?,
                filePathCallback: ValueCallback<Array<Uri>>?,
                fileChooserParams: FileChooserParams?
            ): Boolean {
                if (filePathCallback == null) {
                    return false
                }

                this@MainActivity.filePathCallback?.onReceiveValue(null)
                this@MainActivity.filePathCallback = filePathCallback

                AlertDialog.Builder(this@MainActivity)
                    .setTitle("Upload Reports")
                    .setItems(arrayOf("Camera (multiple photos)", "Choose files")) { _, which ->
                        when (which) {
                            0 -> startCameraCaptureWithPermission()
                            1 -> launchFilePicker()
                            else -> cancelFileChooser()
                        }
                    }
                    .setOnCancelListener {
                        cancelFileChooser()
                    }
                    .show()

                return true
            }
        }

        swipeRefreshLayout.setOnRefreshListener {
            webView.reload()
        }

        swipeRefreshLayout.setOnChildScrollUpCallback { _, _ ->
            webView.canScrollVertically(-1)
        }

        val density = resources.displayMetrics.density
        swipeRefreshLayout.setDistanceToTriggerSync((140f * density).toInt())

        webView.loadUrl("https://doctor.niufin.cloud")
    }

    override fun onRequestPermissionsResult(
        requestCode: Int,
        permissions: Array<out String>,
        grantResults: IntArray
    ) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults)

        if (requestCode != CAMERA_PERMISSION_REQUEST_CODE) {
            return
        }

        val granted = grantResults.isNotEmpty() && grantResults[0] == PackageManager.PERMISSION_GRANTED
        if (granted && isCameraFlowPendingPermission) {
            isCameraFlowPendingPermission = false
            startCameraCaptureSequence()
            return
        }

        isCameraFlowPendingPermission = false
        launchFilePicker()
    }

    private fun startCameraCaptureWithPermission() {
        val cameraPermission = ContextCompat.checkSelfPermission(this, Manifest.permission.CAMERA)
        if (cameraPermission == PackageManager.PERMISSION_GRANTED) {
            startCameraCaptureSequence()
            return
        }

        isCameraFlowPendingPermission = true
        ActivityCompat.requestPermissions(
            this,
            arrayOf(Manifest.permission.CAMERA),
            CAMERA_PERMISSION_REQUEST_CODE
        )
    }

    private fun startCameraCaptureSequence() {
        capturedCameraUris.clear()
        pendingCameraUri = null
        captureNextPhoto()
    }

    private fun captureNextPhoto() {
        val callback = filePathCallback ?: return

        val imageFile = try {
            createImageFile()
        } catch (_: IOException) {
            null
        }

        if (imageFile == null) {
            callback.onReceiveValue(null)
            resetFileChooserState()
            return
        }

        val photoUri = FileProvider.getUriForFile(
            this,
            BuildConfig.APPLICATION_ID + ".fileprovider",
            imageFile
        )

        pendingCameraUri = photoUri
        takePictureLauncher.launch(photoUri)
    }

    private fun showCameraContinueDialog() {
        AlertDialog.Builder(this)
            .setTitle("Add another photo?")
            .setMessage("You have selected ${capturedCameraUris.size} photo(s).")
            .setPositiveButton("Add another") { _, _ ->
                captureNextPhoto()
            }
            .setNegativeButton("Done") { _, _ ->
                val callback = filePathCallback
                if (callback != null) {
                    callback.onReceiveValue(capturedCameraUris.toTypedArray())
                }
                resetFileChooserState()
            }
            .setOnCancelListener {
                val callback = filePathCallback
                if (callback != null) {
                    callback.onReceiveValue(capturedCameraUris.toTypedArray())
                }
                resetFileChooserState()
            }
            .show()
    }

    private fun launchFilePicker() {
        val contentSelectionIntent = Intent(Intent.ACTION_GET_CONTENT)
        contentSelectionIntent.addCategory(Intent.CATEGORY_OPENABLE)
        contentSelectionIntent.type = "*/*"
        contentSelectionIntent.putExtra(
            Intent.EXTRA_MIME_TYPES,
            arrayOf("image/*", "application/pdf")
        )
        contentSelectionIntent.putExtra(Intent.EXTRA_ALLOW_MULTIPLE, true)

        val chooserIntent = Intent(Intent.ACTION_CHOOSER)
        chooserIntent.putExtra(Intent.EXTRA_INTENT, contentSelectionIntent)
        fileChooserLauncher.launch(chooserIntent)
    }

    private fun cancelFileChooser() {
        filePathCallback?.onReceiveValue(null)
        resetFileChooserState()
    }

    private fun resetCameraCaptureState() {
        capturedCameraUris.clear()
        pendingCameraUri = null
        isCameraFlowPendingPermission = false
    }

    private fun resetFileChooserState() {
        filePathCallback = null
        resetCameraCaptureState()
    }

    @Throws(IOException::class)
    private fun createImageFile(): File {
        val timeStamp = SimpleDateFormat("yyyyMMdd_HHmmss", Locale.getDefault()).format(Date())
        val storageDir = getExternalFilesDir(Environment.DIRECTORY_PICTURES)
        return File.createTempFile(
            "JPEG_${timeStamp}_",
            ".jpg",
            storageDir
        )
    }

    /**
     * JavaScript Interface to allow the web page to show native Android Toasts.
     * Usage in JS: Android.showToast("Message");
     */
    class WebAppInterface(private val mContext: Context) {
        @JavascriptInterface
        fun showToast(toast: String) {
            Toast.makeText(mContext, toast, Toast.LENGTH_SHORT).show()
        }
    }
}
