Imports System.IO
Partial Class VB
    Inherits System.Web.UI.Page


    Protected Sub Page_Load(ByVal sender As Object, ByVal e As EventArgs) Handles Me.Load
        If Not IsPostBack Then
            Dim filePaths() As String = Directory.GetFiles(Server.MapPath("~/Uploads/"))
            Dim files As List(Of ListItem) = New List(Of ListItem)
            For Each filePath As String In filePaths
                files.Add(New ListItem(Path.GetFileName(filePath), filePath))
            Next
            GridView1.DataSource = files
            GridView1.DataBind()
        End If
    End Sub

    Protected Sub UploadFile(ByVal sender As Object, ByVal e As EventArgs)
        Dim fileName As String = Path.GetFileName(FileUpload1.PostedFile.FileName)
        FileUpload1.PostedFile.SaveAs((Server.MapPath("~/Uploads/") + fileName))
        Response.Redirect(Request.Url.AbsoluteUri)
    End Sub

    Protected Sub DownloadFile(ByVal sender As Object, ByVal e As EventArgs)
        Dim filePath As String = CType(sender, LinkButton).CommandArgument
        Response.ContentType = ContentType
        Response.AppendHeader("Content-Disposition", ("attachment; filename=" + Path.GetFileName(filePath)))
        Response.WriteFile(filePath)
        Response.End()
    End Sub

    Protected Sub DeleteFile(ByVal sender As Object, ByVal e As EventArgs)
        Dim filePath As String = CType(sender, LinkButton).CommandArgument
        File.Delete(filePath)
        Response.Redirect(Request.Url.AbsoluteUri)
    End Sub

End Class
