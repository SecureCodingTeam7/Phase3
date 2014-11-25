package scs;

import javafx.event.ActionEvent;
import javafx.fxml.FXML;
import javafx.fxml.Initializable;
import javafx.scene.control.Button;
import javafx.scene.control.Label;
import javafx.scene.control.TextField;
import javafx.scene.input.Clipboard;
import javafx.scene.input.ClipboardContent;
import javafx.scene.paint.Color;

import java.io.IOException;
import java.net.URL;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.util.Random;
import java.util.ResourceBundle;

public class Controller implements Initializable {

    @FXML
    private Label statusLabel;
    @FXML
    private TextField destinationTextField;
    @FXML
    private TextField pinTextField;
    @FXML
    private TextField amountTextField;
    @FXML
    private Button copyTANButton;

    String tan;

    @Override
    public void initialize(URL location, ResourceBundle resources) {
        statusLabel.setVisible(false);
        copyTANButton.setVisible(false);
    }

    @FXML
    private void copyTANClicked(ActionEvent event) {
        final Clipboard clipboard = Clipboard.getSystemClipboard();
        final ClipboardContent content = new ClipboardContent();
        content.putString(tan);
        clipboard.setContent(content);
    }

    @FXML
    private void generateTANClicked(ActionEvent event) {
        statusLabel.setVisible(false);
        copyTANButton.setVisible(false);

        if(!validateInputs()) {
            return;
        }

        String pin = pinTextField.getText();
        String destination = destinationTextField.getText();
        String amount = amountTextField.getText();

        long time;
        try {
            time = NetworkTime.getTime();
        } catch (IOException ex) {
            ex.printStackTrace();
            setNegativeStatus("Could not reach time server!");
            return;
        }

        long seed = time - time % (1 * 60);
        System.out.println(seed);
        Random random = new Random(seed);

        MessageDigest md5Digest;

        try {
            md5Digest = MessageDigest.getInstance("MD5");
        } catch (NoSuchAlgorithmException e) {
            // this should normally not happen!
            e.printStackTrace();
            setNegativeStatus("Could not get md5 digest!");
            return;
        }

        byte[] md5 = md5Digest.digest((pin + destination + amount).getBytes());

        byte[] randoms = new byte[15];
        random.nextBytes(randoms);

        byte[] result = new byte[15];

        for(int i = 0; i < result.length; i++) {
            int xor = randoms[i] ^ md5[i];
            result[i] = (byte)(0xff & xor);
        }

        StringBuffer stringBuffer = new StringBuffer();

        for(byte b : result) {
            stringBuffer.append(Math.abs(b));
        }

        tan = stringBuffer.toString().substring(0, 15);

        setPositiveStatus("Your TAN: " + tan);
    }

    private boolean validateInputs() {

        String pin = pinTextField.getText();
        String destination = destinationTextField.getText();
        String amount = amountTextField.getText();

        if(pin.isEmpty() || destination.isEmpty() || amount.isEmpty()) {
            setNegativeStatus("Please fill in all fields!");
            return false;
        }

        if(pin.length() != 6) {
            setNegativeStatus("PIN must have exactly six digits!");
            return false;
        }

        if(!pin.matches("[0-9]+")) {
            setNegativeStatus("PIN must contain only digits!");
            return false;
        }

        if(destination.length() != 10) {
            setNegativeStatus("Destination must have exactly ten digits!");
            return false;
        }

        if(!destination.matches("[0-9]+")) {
            setNegativeStatus("Destination must contain only digits!");
            return false;
        }

        if(!amount.matches("[0-9]+(\\.\\d{1,2})?")) {
            setNegativeStatus("Amount must be a valid amount!");
            return false;
        }

        return true;
    }

    private void setNegativeStatus(String errorMessage) {
        statusLabel.setTextFill(Color.FIREBRICK);
        statusLabel.setText(errorMessage);
        statusLabel.setVisible(true);
        copyTANButton.setVisible(false);
    }

    private void setPositiveStatus(String message) {
        statusLabel.setTextFill(Color.GREEN);
        statusLabel.setText(message);
        statusLabel.setVisible(true);
        copyTANButton.setVisible(true);
    }
}
