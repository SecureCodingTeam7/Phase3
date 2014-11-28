/* This code will query a ntp server for the local time and display
 * it.  it is intended to show how to use a NTP server as a time
 * source for a simple network connected device.
 * This is the C version.  The orignal was in Perl
 *
 * For better clock management see the offical NTP info at:
 * http://www.eecis.udel.edu/~ntp/
 *
 * written by Tim Hogard (thogard@abnormal.com)
 * Thu Sep 26 13:35:41 EAST 2002
 * Converted to C Fri Feb 21 21:42:49 EAST 2003
 * this code is in the public domain.
 * it can be found here http://www.abnormal.com/~thogard/ntp/
 *
 * ported to android 4.3 on mar/nov/5 - 2013 by abel.
 * the same day ported to a library for agpsd layer.
 * 
 * Adapted to let it run on unix system
 * 
 * Source: http://stackoverflow.com/questions/10757575/how-to-write-a-ntp-client
 */

#include "ntp.h"

#include <stdio.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <netdb.h>
#include <time.h>
#include <string.h>
#include <stdlib.h>
#include <unistd.h>
#include <errno.h>
#include <math.h>
#include <sys/time.h>
#include <stdint.h>

#define NTP_MODE_CLIENT 3
#define NTP_VERSION 3
#define TRANSMIT_TIME_OFFSET 40
#define REFERENCE_TIME_OFFSET 16
#define ORIGINATE_TIME_OFFSET 24
#define RECEIVE_TIME_OFFSET 32
#define OFFSET_1900_TO_1970 ((uint64_t)((365 * 70) + 17) * 24 * 60 * 60)

void ntpdate(uint64_t *cachedTime, uint64_t *cachedTimeRef, uint64_t *cacheCertainty);

/*int main() {
    uint64_t cachedTime, cachedTimeRef, cacheCertainty;
    ntpdate(&cachedTime, &cachedTimeRef, &cacheCertainty);
    printf ("%lld\n, %lld\n, %lld\n", cachedTime / 1000, cachedTimeRef, cacheCertainty);
    return 0;
}*/

double random2 () {
    srandom(time(NULL));
    return random();
}

uint64_t currentTimeMillis(/*long int seconds, long int miliseconds*/) {
    struct timeval te; 
    gettimeofday(&te, NULL); // get current time
    uint64_t millis = (uint64_t) te.tv_sec * 1000 + floor(te.tv_usec / 1000); // caculate milliseconds
//  printf ("millis: %llu\n", millis);
    return millis;
}

uint32_t read32(char* buffer, int offset) {
    char b0 = buffer[offset];
    char b1 = buffer[offset+1];
    char b2 = buffer[offset+2];
    char b3 = buffer[offset+3];

    // convert signed bytes to unsigned values
    uint32_t i0 = ((b0 & 0x80) == 0x80 ? (b0 & 0x7F) + 0x80 : b0);
    uint32_t i1 = ((b1 & 0x80) == 0x80 ? (b1 & 0x7F) + 0x80 : b1);
    uint32_t i2 = ((b2 & 0x80) == 0x80 ? (b2 & 0x7F) + 0x80 : b2);
    uint32_t i3 = ((b3 & 0x80) == 0x80 ? (b3 & 0x7F) + 0x80 : b3);

    uint32_t v = (i0 << 24) + (i1 << 16) + (i2 << 8) + i3;
    return v;
}

uint64_t readTimeStamp(char *buffer, int offset) {
    uint32_t seconds  = read32(buffer, offset);
    uint32_t fraction = read32(buffer, offset + 4);
    uint64_t v = ((int64_t)seconds - OFFSET_1900_TO_1970) * 1000 + (int64_t) fraction * 1000 / (int64_t) 0x100000000;
//  printf ("%llu\n", v);
    return v;
}


void writeTimeStamp(char* buffer, int offset, long long int time) {
    uint64_t seconds = floor (time / 1000);
    uint64_t milliseconds = time - (uint64_t) seconds * 1000;
    seconds += OFFSET_1900_TO_1970;

    // write seconds in big endian format
    buffer[offset++] = (char)(seconds >> 24);
    buffer[offset++] = (char)(seconds >> 16);
    buffer[offset++] = (char)(seconds >> 8);
    buffer[offset++] = (char)(seconds >> 0);

    uint64_t fraction = round ((uint64_t)milliseconds * 0x100000000 / 1000);
    // write fraction in big endian format
    buffer[offset++] = (char)(fraction >> 24);
    buffer[offset++] = (char)(fraction >> 16);
    buffer[offset++] = (char)(fraction >> 8);
    // low order bits should be random data
    buffer[offset++] = (char)(random2() * 255.0);
}

void ntpdate(uint64_t *cachedTime, uint64_t *cachedTimeRef, uint64_t *cacheCertainty) {
    char hostname[]="81.184.154.182";
    int portno=123;     //NTP is port 123
    int maxlen=48;        //check our buffers
    int i;          // misc var i
    uint64_t requestTime = currentTimeMillis();
    struct timeval tv;

	gettimeofday(&tv, NULL);

	unsigned long long millisecondsSinceEpoch =
    (unsigned long long)(tv.tv_sec) * 1000 +
    (unsigned long long)(tv.tv_usec) / 1000;
    
    uint64_t requestTicks = millisecondsSinceEpoch;
    char msg[48];
    memset (msg,0,sizeof(msg));
    msg[0]= NTP_MODE_CLIENT | (NTP_VERSION << 3);
    writeTimeStamp(msg, TRANSMIT_TIME_OFFSET, requestTime);
    char  buf[maxlen]; // the buffer we get back
    struct sockaddr_in server_addr;
    int s;  // socket
    //time_t tmit;   // the time -- This is a time_t sort of

    s=socket(PF_INET, SOCK_DGRAM, IPPROTO_UDP);
    memset( &server_addr, 0, sizeof( server_addr ));
    server_addr.sin_family=AF_INET;
    server_addr.sin_addr.s_addr = inet_addr(hostname);
    server_addr.sin_port=htons(portno);

    int retries = 0;
    while(retries < 10) {
    	i=sendto(s,msg,sizeof(msg),0,(struct sockaddr *)&server_addr,sizeof(server_addr));
    	if(i<=0) {
			sleep(800);
			retries++;
			continue;
		}

		struct sockaddr saddr;
		socklen_t saddr_l = sizeof (saddr);
		i=recvfrom(s,buf,sizeof(buf),0,&saddr,&saddr_l);
		if(i<=0) {
			sleep(800);
			retries++;
			continue;
		}

		// success
		break;
    }

    if(retries == 10) {
    	printf("Error: could not reach time server!");
    	return;
    }


	gettimeofday(&tv, NULL);

	millisecondsSinceEpoch =
    (unsigned long long)(tv.tv_sec) * 1000 +
    (unsigned long long)(tv.tv_usec) / 1000;

    uint64_t responseTicks = millisecondsSinceEpoch;
    uint64_t responseTime = requestTime + (responseTicks - requestTicks);
    uint64_t originateTime = readTimeStamp(buf, ORIGINATE_TIME_OFFSET);
    uint64_t receiveTime = readTimeStamp(buf, RECEIVE_TIME_OFFSET);
    uint64_t transmitTime = readTimeStamp(buf, TRANSMIT_TIME_OFFSET);
    uint64_t roundTripTime = responseTicks - requestTicks - (transmitTime - receiveTime);
    int32_t clockOffset = ((receiveTime - originateTime) + (transmitTime - responseTime))/2;
    //printf ("%lld + %lld = %ld %ld\n", (receiveTime - originateTime), (transmitTime - responseTime), (receiveTime - originateTime + transmitTime - responseTime)/2, clockOffset);
    uint64_t mNtpTime = responseTime + clockOffset;
    uint64_t mNtpTimeReference = responseTicks;
    uint64_t mRoundTripTime = roundTripTime;
    uint64_t mCachedNtpTime = mNtpTime;
    //uint64_t mCachedNtpElapsedRealtime = mNtpTimeReference;
    uint64_t mCachedNtpCertainty = mRoundTripTime / 2;
    *cachedTime     = mCachedNtpTime;
    *cachedTimeRef  = mNtpTimeReference;
    *cacheCertainty = mCachedNtpCertainty;

//  uint64_t now = mNtpTime + android::elapsedRealtime() - mNtpTimeReference;
/*
    printf ("%lld\n", now);
    i=currentTimeMillis();
    printf("System time is %lu miliseconds off\n",i-now);
*/
}
